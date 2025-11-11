# VeA_EconModels
Skolas darbs bakalaurdarba ietvaros: Ekonomikas teorijas stohastiskās optimizācijas algoritmi.

## Prerequisites
- Docker Desktop
- Git
- (Optional) Composer and PHP for local non-Docker development


## Quick start (Docker)
1. Clone repo:

git clone https://github.com/TomassMil/VeA_EconModels.git
cd VeA_EconModels

2. Build and start containers:

docker compose up -d --build

3. Install dependencies and run migrations:

docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate

4. Open in browser
http://localhost:8082


Development notes:
App code is in src/
Nginx config: docker/nginx/default.conf
PHP Dockerfile: docker/php/Dockerfile
DB: MySQL, credentials in docker-compose.yml

