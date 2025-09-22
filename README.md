# 📰 News Admin Panel

A simple PHP 8.3 news management website with login, admin panel, and news CRUD.

## 🛠 Tech Stack

- PHP 8.3
- MySQL
- Redis
- Twig templates
- Vanilla JS
- Docker
- HTML & CSS

## 🚀 Getting Started

### 1. Clone repository

`git clone <repository-url>`

`cd <repository-folder>`

### 2. Configure environment

Copy the example .env.example to .env and adjust values if needed:

`cp .env.example .env`

### 3. Build and start Docker containers
`docker compose up -d --build`

### 4. Run database migrations
`docker compose exec app php migrate.php`

### 5. Access the application
http://localhost:8080

Default login credentials:

- **Username**: `admin`
- **Password**: `test`