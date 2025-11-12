# 🚀 VeA_EconModels — Server Deployment Guide

## 1️⃣ Klonē projektu
```bash
git clone https://github.com/TomassMil/VeA_EconModels.git
cd VeA_EconModels
```

## 2️⃣ Izveido `.env` failu
```bash
cp src/.env.example src/.env
```

## 3️⃣ Docker palaišana
```bash
docker compose up --build -d
```

## 4️⃣ Laravel konfigurācija
```bash
docker compose exec app bash
php artisan key:generate
php artisan migrate
php artisan session:table
php artisan migrate
exit
```

## 5️⃣ Pārbaude
Atver pārlūkā:  
👉 http://localhost:8082

Ja redzi Laravel lapu — viss strādā! 🎉

---

**Autors:** Toms Millers
Ventspils Augstskola — Bakalaura darbs  
