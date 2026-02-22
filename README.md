# Backend (Laravel API)

This is the backend for the Product Master List application, built with Laravel.

## 📖 Documentation

For full project setup, prerequisites, and running instructions, please refer to the main [README](README_MAIN.md).

## 🛠️ Key Commands

- **Install Dependencies**: `composer install`
- **Run Migrations**: `php artisan migrate`
- **Seed Database**: `php artisan db:seed`
- **Run Tests**: `php artisan test`
- **Run Dev Server**: `php artisan serve`
- **Run Queue Worker**: `php artisan queue:work`
- **Run All Services**: `composer run dev` (Runs backend, frontend, queue, and websocket)

## 🚀 Running the Application

To set up and run the backend (and all associated services):

```bash
composer install
composer run dev
```

This will start:
- Laravel Server (`http://localhost:8000`)
- Queue Worker
- WebSocket Server
- Frontend Vite Server (`http://localhost:5173`)
