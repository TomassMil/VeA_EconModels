<?php

namespace App\Http\Controllers;

use App\Models\Instrument;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class InstrumentController extends Controller
{
    public function index(Request $request): View
    {
        $rawSearch = trim((string) $request->query('q', ''));
        $search = mb_substr($rawSearch, 0, 100);

        $query = Instrument::query()
            ->select(['id', 'ticker', 'company_name', 'exchange']);

        if ($search !== '') {
            $escapedSearch = $this->escapeLike($search);
            $contains = '%'.$escapedSearch.'%';
            $prefix = $escapedSearch.'%';

            $query->where(function ($builder) use ($contains) {
                $builder->where('ticker', 'like', $contains)
                    ->orWhere('company_name', 'like', $contains);
            })->orderByRaw(
                'case when ticker like ? then 0 when company_name like ? then 1 else 2 end',
                [$prefix, $prefix]
            );
        }

        $instruments = $query
            ->orderBy('ticker')
            ->orderBy('exchange')
            ->paginate(25)
            ->withQueryString();

        return view('instruments.index', [
            'instruments' => $instruments,
            'search' => $search,
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $rawSearch = trim((string) $request->query('q', ''));
        $search = mb_substr($rawSearch, 0, 100);

        if ($search === '') {
            return response()->json(['data' => []]);
        }

        $escapedSearch = $this->escapeLike($search);
        $contains = '%'.$escapedSearch.'%';
        $prefix = $escapedSearch.'%';

        $instruments = Instrument::query()
            ->select(['id', 'ticker', 'company_name', 'exchange'])
            ->where(function ($builder) use ($contains) {
                $builder->where('ticker', 'like', $contains)
                    ->orWhere('company_name', 'like', $contains);
            })
            ->orderByRaw(
                'case when ticker like ? then 0 when company_name like ? then 1 else 2 end',
                [$prefix, $prefix]
            )
            ->orderBy('ticker')
            ->limit(12)
            ->get();

        return response()->json(['data' => $instruments]);
    }

    public function show(Instrument $instrument): View
    {
        $priceSeries = DB::table('prices_daily')
            ->select(['date', 'open', 'high', 'low', 'close', 'volume'])
            ->where('instrument_id', $instrument->id)
            ->where(function ($query) {
                $query->whereNotNull('close')
                    ->orWhereNotNull('open')
                    ->orWhereNotNull('high')
                    ->orWhereNotNull('low')
                    ->orWhereNotNull('volume');
            })
            ->orderBy('date')
            ->get()
            ->values();

        return view('instruments.show', [
            'instrument' => $instrument,
            'priceSeries' => $priceSeries,
        ]);
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}
