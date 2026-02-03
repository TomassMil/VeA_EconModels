# 💻 VeA_EconModels — Economic Models Information System

Laravel + Nginx + MySQL application running in Docker containers.  
Developed as a bachelor's thesis project at Ventspils University of Applied Sciences.

---

## 🚀 Quick Start (3 Steps)

### 1️⃣ Clone the repository
```bash
git clone https://github.com/TomassMil/VeA_EconModels.git
cd VeA_EconModels
```

### 2️⃣ Start the application
**Development:**
```bash
docker compose -f docker-compose.yml -f docker-compose.dev.yml up -d --build
```

**Production:**
```bash
docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d --build
```

### 3️⃣ Open in browser
**Development:** 👉 [http://localhost:8080](http://localhost:8080)  
**Production:** 👉 [https://econmodels.venta.lv](https://econmodels.venta.lv)

**That's it** The application automatically:
- Sets up the database
- Runs migrations
- Configures permissions
- Starts all services

---

## 📁 Project Structure
```
VeA_EconModels/
│
├── src/                          # Laravel application code
├── docker/
│   ├── php/
│   │   ├── Dockerfile            # PHP 8.3-FPM container
│   │   ├── entrypoint.sh         # Startup automation script
│   │   └── conf.d/               # PHP configuration
│   └── nginx/
│       ├── default.conf          # Production Nginx config
│       └── default.dev.conf      # Development Nginx config
├── docker-compose.yml            # Base Docker configuration
├── docker-compose.dev.yml        # Development overrides
├── docker-compose.prod.yml       # Production overrides
└── README.md
```

---

## 🧩 Prerequisites

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) (includes Docker Compose)
- [Git](https://git-scm.com/)

**Note:** You don't need to install PHP, Composer, or MySQL locally. Everything runs in containers.

---

## 🐳 What Happens on Startup

When you run `docker compose up`, the application automatically:

1. ✅ Builds three containers: **app** (Laravel), **nginx** (web server), **db** (MySQL)
2. ✅ Waits for the database to be ready
3. ✅ Runs database migrations
4. ✅ Sets correct file permissions
5. ✅ Optimizes for production (if using prod config)
6. ✅ Starts serving requests

No manual intervention required!

---

## 🧠 Container Architecture

| Container | Purpose | Technology |
|-----------|---------|------------|
| **app** | Runs Laravel application | PHP 8.3-FPM |
| **nginx** | Web server & reverse proxy | Nginx stable |
| **db** | Database storage | MySQL 8.0 |

All containers communicate through the `vea_net` Docker network.

---

## 🪄 Useful Commands

### Container Management
```bash
# Start containers (detached mode)
docker compose -f docker-compose.yml -f docker-compose.dev.yml up -d

# Stop containers
docker compose -f docker-compose.yml -f docker-compose.dev.yml down

# Rebuild after code changes
docker compose -f docker-compose.yml -f docker-compose.dev.yml up -d --build

# View running containers
docker ps

# View logs
docker logs vea_econmodels_app -f
docker logs vea_econmodels_nginx -f
docker logs vea_econmodels_db -f
```

### Laravel Commands (if needed)
```bash
# Access Laravel container
docker exec -it vea_econmodels_app bash

# Run Artisan commands
docker exec -it vea_econmodels_app php artisan <command>

# Examples:
docker exec -it vea_econmodels_app php artisan migrate:status
docker exec -it vea_econmodels_app php artisan cache:clear
docker exec -it vea_econmodels_app php artisan config:clear
```

### Database Access
```bash
# Connect to MySQL
docker exec -it vea_econmodels_db mysql -u laravel -plaravelpass econmodels

# Or as root
docker exec -it vea_econmodels_db mysql -u root -prootpassword
```

---

## 🔧 Environment Configuration

The `.env` file is created automatically from `.env.example`. Default database credentials:
```env
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=econmodels
DB_USERNAME=laravel
DB_PASSWORD=laravelpass
```

**For production:** Update `.env` with secure credentials before deploying.

---

## 🌐 Port Mappings

**Development:**
- Application: `http://localhost:8080`
- MySQL: `localhost:3307` (for external tools like MySQL Workbench)

**Production:**
- Application: `http://localhost:80` and `https://localhost:443`
- MySQL: `localhost:3307`

---

## 🔍 Troubleshooting

### Application not loading?
```bash
# Check container status
docker ps

# View application logs
docker logs vea_econmodels_app --tail 50

# Check if all containers are running
docker compose -f docker-compose.yml -f docker-compose.dev.yml ps
```

### Permission errors?
The entrypoint script automatically fixes permissions. If issues persist:
```bash
docker compose -f docker-compose.yml -f docker-compose.dev.yml restart app
```

### Database connection errors?
```bash
# Verify database is running
docker exec vea_econmodels_app php artisan db:show

# Check migration status
docker exec vea_econmodels_app php artisan migrate:status
```

---

## 📦 Development vs Production

| Feature | Development | Production |
|---------|-------------|------------|
| **Port** | 8080 | 80, 443 (HTTPS) |
| **PHP Config** | opcache-dev.ini | opcache-prod.ini |
| **Caching** | Disabled | Enabled (config, routes, views) |
| **SSL/TLS** | No | Yes (Let's Encrypt) |
| **Error Display** | Detailed | Hidden |

---

## 📄 License

This project is developed for academic purposes as part of a bachelor's thesis at Ventspils University of Applied Sciences.

**Author:** Toms Millers  
**Institution:** Ventspils Augstskola  
**Year:** 2025-2026

---

## 🤝 Contributing

This is an academic project. For questions or suggestions, please open an issue on GitHub.
