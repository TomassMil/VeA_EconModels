# 💻 VeA_EconModels — Lokālās instalācijas un izstrādes pamācība

Šis projekts ir Laravel + Nginx + MySQL vide, kas darbojas ar Docker konteineriem.  
Mērķis — izstrādāt un palaist ekonomikas modeļu informācijas sistēmu.

---

## 📁 Projekta struktūra
```
VeA_EconModels/
│
├── src/                # Laravel lietotnes kods
├── docker/
│   ├── php/            # PHP (Laravel) konteineris
│   ├── nginx/          # Nginx konfigurācija
│   └── mysql/          # Datubāzes konfigurācija
├── docker-compose.yml  # Docker Compose konfigurācija
└── README.md
```

---

## 🧩 Priekšnosacījumi
Pirms sāc, pārliecinies, ka tev ir uzstādīts:
- [Docker Desktop](https://www.docker.com/products/docker-desktop/)
- [Git](https://git-scm.com/)
- [Composer](https://getcomposer.org/) (ja vēlies lokāli instalēt Laravel pakotnes)

---

## 🛠️ Instalācijas soļi

### 1️⃣ Klonē repozitoriju
```bash
git clone https://github.com/TomassMil/VeA_EconModels.git
cd VeA_EconModels
```

### 2️⃣ Izveido `.env` failu
```bash
cp src/.env.example src/.env
```

### 3️⃣ Palaiž Docker vidi
```bash
docker compose up --build -d
```

Tas izveidos trīs konteinerus:
- **app** — Laravel (PHP 8.3-fpm)
- **nginx** — serveris, kas apkalpo HTTP pieprasījumus
- **db** — MySQL 8.0 datubāze

---

## ⚙️ Laravel konfigurācija
Kad konteineri darbojas:

```bash
docker compose exec app bash
php artisan key:generate
php artisan migrate
php artisan session:table
php artisan migrate
exit
```

---

## 🌍 Pārbaudi projektu
Atver pārlūkā:

👉 [http://localhost:8082](http://localhost:8082)

Ja redzi Laravel sākumlapu — viss darbojas pareizi ✅

---

## 🧠 Papildus informācija

- **Nginx** — apkalpo HTTP pieprasījumus, un tos pārsūta uz PHP konteineru.  
- **Laravel (PHP-FPM)** — interpretē PHP kodu un savienojas ar datubāzi.  
- **MySQL** — glabā ekonomikas modeļu, lietotāju un sesiju datus.  
- **Docker Compose** — savieno šos konteinerus vienotā tīklā (`vea_net`).  

---

## 🪄 Noderīgas komandas

| Komanda | Apraksts |
|----------|-----------|
| `docker compose up -d` | Palaiž konteinerus fonā |
| `docker compose down` | Aptur un izdzēš konteinerus |
| `docker compose exec app bash` | Ieej Laravel konteinerā |
| `php artisan migrate` | Palaid datubāzes migrācijas |
| `docker ps` | Apskati aktīvos konteinerus |
| `docker logs nginx` | Skati Nginx žurnālus |

---

## 📄 Licence
Šis projekts paredzēts akadēmiskai lietošanai (Ventspils Augstskola, datorzinātņu bakalaura darbs).
