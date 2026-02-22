# PHP Developer Assignment - Product Master List

This project is a solution for the PHP Developer Assessment. It involves a Laravel backend, Vue.js frontend, and MySQL database to manage a product master list with Excel upload capabilities.

## 📂 Folder Architecture

The project is organized into two main directories:

- **backend/**: Contains the Laravel API application.
  - `app/`: Core application logic (Controllers, Models, Jobs).
  - `database/`: Migrations and Seeders.
  - `routes/`: API routes definition.
  - `tests/`: Unit and Feature tests.
  - `ws-server.php`: WebSocket server entry point.
- **frontend/**: Contains the Vue.js 3 application.
  - `src/`: Source code for Vue components and views.
  - `public/`: Static assets.
  - `vite.config.js`: Vite configuration.

## 🛠️ Prerequisites

Ensure you have the following software installed:

- **PHP**: ^8.2
- **Composer**: Latest version
- **Node.js**: ^18.0 or higher
- **NPM**: Included with Node.js
- **MySQL**: 8.0 or higher (or use Docker as configured)

## 🚀 Setup Guide

### 1. Environment Configuration (.env)

You need to configure the environment variables for both the backend and frontend.

#### Backend (.env)
Navigate to the `backend` folder and copy the example file:
```bash
cd backend
cp .env.example .env
```
Open `.env` and configure your database settings (change `sqlite` to `mysql`):
```ini
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Ensure Queue Connection is set to database or redis for background jobs
QUEUE_CONNECTION=database
```

#### Frontend (.env)
Navigate to the `frontend` folder and create/edit `.env`:
```bash
cd ../frontend
# If .env.example exists: cp .env.example .env
# Otherwise create a new .env file
touch .env
```
Add the API URL to `frontend/.env`:
```ini
VITE_API_URL=http://127.0.0.1:8000/api
```

### 2. Backend Setup

Before running the application, you must set up the backend dependencies and database.

```bash
# Navigate to backend directory
cd backend

# Install PHP dependencies
composer install

# Generate Application Key
php artisan key:generate

# Run Database Migrations
php artisan migrate

# Seed the Database (Optional but recommended for testing)
php artisan db:seed
```

### 3. Frontend Setup

```bash
# Navigate to frontend directory
cd ../frontend

# Install Node dependencies
npm install
```

## 🏃‍♂️ Running the Application

### 🖥️ Frontend
To set up and run the frontend independently:

```bash
cd frontend
npm install
npm run dev
```
This will start the Vite development server at `http://localhost:5173`.

### ⚙️ Backend
To set up and run the backend (and all associated services):

```bash
cd backend
composer install
composer run dev
```
The `composer run dev` command is a convenient shortcut that uses `concurrently` to start all necessary services at once:
- Laravel Development Server (`php artisan serve`) at `http://localhost:8000`
- Queue Worker (`php artisan queue:listen`)
- Laravel Pail (Logging)
- WebSocket Server (`php ws-server.php`)
- **Frontend Vite Server** (it automatically runs `npm run dev` for you!)

## 📜 List of Commands

| Command | Description |
| :--- | :--- |
| `composer install` | Install backend PHP dependencies |
| `npm install` | Install frontend Node.js dependencies |
| `php artisan migrate` | Run database migrations |
| `php artisan db:seed` | Seed the database with initial data |
| `php artisan queue:work` | Start the queue worker manually |
| `php artisan serve` | Start the Laravel development server manually |
| `npm run dev` | Start the frontend dev server manually |
| `composer run dev` | Start all services (Backend + Frontend + Queue + WS) |

## 🧪 Testing

To run backend tests:
```bash
cd backend
php artisan test
```
