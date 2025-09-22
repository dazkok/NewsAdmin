# 📰 News Admin Panel

A simple PHP 8.3 news management website with login, admin panel, and news CRUD operations.

## 🛠 Tech Stack

- **PHP 8.3** with Object-Oriented Programming
- **MySQL** for database storage
- **Redis** for caching
- **Twig** templates for views
- **Vanilla JavaScript**
- **Docker** for containerization
- **Pure HTML & CSS**

## 🚀 Quick Start

### 1. Clone and setup
```bash
git clone https://github.com/dazkok/NewsAdmin <repository-folder>
cd <repository-folder>
```

### 2. Configure environment
Copy the example .env.example to .env and adjust values if needed:
```bash
cp .env.example .env
```

### 3. Start the application
```bash
docker compose up -d --build
```

### 4. Setting up Composer dependencies
```bash
docker compose exec app composer install
```

### 5. Run database migrations
```bash
docker compose exec app php migrate.php
```

### 6. Access the application
http://localhost:8080

Default login credentials:

- **Username**: `admin`
- **Password**: `test`