#!/usr/bin/env python3
"""
Ģenerē QuantStats HTML atskaiti dotam portfelim.

Lietošana:
    python3 generate_quantstats.py <portfolio_id> <output_html_path>

Lasa portfeļa transakcijas no PostgreSQL datubāzes, rekonstruē dienas vērtību sēriju
un izsauc quantstats.reports.html() ar SPY kā benchmark.

Atkarības:
    pip install quantstats psycopg2-binary pandas numpy
"""

import os
import sys
from pathlib import Path

import numpy as np
import pandas as pd
import psycopg2
import quantstats as qs


def db_connect():
    """Pieslēdzas datubāzei izmantojot env mainīgos."""
    return psycopg2.connect(
        host=os.getenv("DB_HOST", "db"),
        port=int(os.getenv("DB_PORT", "5432")),
        dbname=os.getenv("DB_DATABASE", "vea_econmodels"),
        user=os.getenv("DB_USERNAME", "postgres"),
        password=os.getenv("DB_PASSWORD", ""),
    )


def load_portfolio_value_series(conn, portfolio_id: int) -> pd.Series:
    """
    Rekonstruē portfeļa kopējās vērtības dienas sēriju.

    Loģika:
      1. Lasa visas transakcijas, sakārtotas pēc datuma.
      2. Katrai tirdzniecības dienai uztur cash bilanci un akciju daudzumu.
      3. Aprēķina kopējo vērtību kā cash + Σ(shares × close).
    """
    cur = conn.cursor()
    cur.execute(
        """
        SELECT instrument_id, type, transaction_date, shares, amount
        FROM portfolio_transactions
        WHERE portfolio_id = %s
        ORDER BY transaction_date, id
        """,
        (portfolio_id,),
    )
    txns = cur.fetchall()
    if not txns:
        raise ValueError(f"Portfelim {portfolio_id} nav transakciju")

    start_date = min(t[2] for t in txns)
    end_date = pd.Timestamp.today().date()

    instrument_ids = list({t[0] for t in txns if t[0] is not None})

    if instrument_ids:
        cur.execute(
            """
            SELECT instrument_id, time::date AS date, close
            FROM prices_daily
            WHERE instrument_id = ANY(%s)
              AND time >= %s AND time <= %s
              AND close IS NOT NULL
            ORDER BY time
            """,
            (instrument_ids, start_date, end_date),
        )
        prices = pd.DataFrame(cur.fetchall(), columns=["instrument_id", "date", "close"])
        prices["date"] = pd.to_datetime(prices["date"])
        price_pivot = prices.pivot(index="date", columns="instrument_id", values="close").ffill()
    else:
        price_pivot = pd.DataFrame()

    trading_dates = sorted(price_pivot.index.unique()) if not price_pivot.empty else \
        pd.date_range(start_date, end_date, freq="B")

    cash = 0.0
    positions: dict[int, float] = {}
    txn_idx = 0
    n_txns = len(txns)
    values = []

    for date in trading_dates:
        date_only = date.date() if hasattr(date, "date") else date
        while txn_idx < n_txns and txns[txn_idx][2] <= date_only:
            iid, ttype, tdate, shares, amount = txns[txn_idx]
            cash += float(amount)
            if iid is not None and shares is not None:
                positions[iid] = positions.get(iid, 0.0) + float(shares)
                if abs(positions[iid]) < 1e-9:
                    del positions[iid]
            txn_idx += 1

        market_value = 0.0
        for iid, sh in positions.items():
            if not price_pivot.empty and iid in price_pivot.columns and date in price_pivot.index:
                price = price_pivot.at[date, iid]
                if pd.notna(price):
                    market_value += sh * float(price)

        values.append((date, cash + market_value))

    series = pd.Series(
        [v for _, v in values],
        index=pd.DatetimeIndex([d for d, _ in values]),
        name=f"portfolio_{portfolio_id}",
    )
    series = series[series > 0]
    return series


def main():
    if len(sys.argv) < 3:
        print("Lietošana: generate_quantstats.py <portfolio_id> <output_path>", file=sys.stderr)
        sys.exit(1)

    portfolio_id = int(sys.argv[1])
    output_path = Path(sys.argv[2])
    output_path.parent.mkdir(parents=True, exist_ok=True)

    conn = db_connect()
    try:
        values = load_portfolio_value_series(conn, portfolio_id)
    finally:
        conn.close()

    if len(values) < 2:
        print("Nepietiek datu QuantStats atskaitei (vajadzīgs vismaz 2 dienas)", file=sys.stderr)
        sys.exit(2)

    returns = values.pct_change().dropna()
    returns = returns.replace([np.inf, -np.inf], np.nan).dropna()

    qs.extend_pandas()
    qs.reports.html(
        returns,
        benchmark="SPY",
        output=str(output_path),
        title=f"Portfeļa #{portfolio_id} atskaite",
        download_filename=output_path.name,
    )
    print(f"OK: {output_path}")


if __name__ == "__main__":
    main()
