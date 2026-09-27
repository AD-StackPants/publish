# Posexei — Developer Setup & Self-Hosting Guide

This guide is intended for developers, DevOps engineers, and technical teams setting up Posexei locally or self-hosting on private infrastructure.

---

## 🛠️ Prerequisites

Ensure your host environment meets the following requirements:
* **PHP**: 8.2 or higher (with `mbstring`, `pdo_sqlite` or `pdo_pgsql`, `curl`, `openssl`, `tokenizer`)
* **Composer**: 2.5 or higher
* **Node.js**: 20.x or higher
* **Package Manager**: `npm` or `pnpm`
* **Database**: SQLite (default for development) or PostgreSQL 15+ (production multi-tenant)

---

## 🚀 Local Installation Steps

### 1. Clone the Repository
```bash
git clone https://github.com/your-org/SocialSyncPost.git
cd SocialSyncPost
```

### 2. Install Backend Dependencies
```bash
composer install
```

### 3. Install Frontend Dependencies
```bash
npm install
```

### 4. Configure Environment
```bash
cp .env.example .env
php artisan key:generate
```

Ensure your `.env` has appropriate application and database configurations:
```env
APP_NAME=Posexei
APP_ENV=local
APP_KEY=base64:...
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=sqlite
# Or PostgreSQL for production:
# DB_CONNECTION=pgsql
# DB_HOST=127.0.0.1
# DB_PORT=5432
# DB_DATABASE=posexei_db
# DB_USERNAME=posexei_user
# DB_PASSWORD=secret
```

### 5. Run Database Migrations
Posexei includes raw Laravel migrations for organizations, social accounts, posts, delivery checkpoints, plans, subscriptions, and billing invoices:
```bash
php artisan migrate
```

### 6. Start the Development Server
Use the unified development runner:
```bash
composer run dev
```
This concurrently starts:
* Laravel HTTP server on `http://127.0.0.1:8000`
* Vite HMR dev server on `http://localhost:5173`

---

## 🧪 Static Analysis & Verification

Run static type checking across Vue 3 and TypeScript components:
```bash
npm run types:check
# or: npx vue-tsc --noEmit
```

Run Laravel Pint for PHP code formatting:
```bash
./vendor/bin/pint --test
```

---

## 📂 Architecture Overview

* **Frontend Framework**: Vue 3 `<script setup lang="ts">` with Inertia.js v3.
* **Styling & Tokens**: Tailwind CSS v4 with `@ad-technology-inc/design-system`.
* **State Management**: Pinia stores (`resources/js/stores/workspace.ts`, `resources/js/stores/billing.ts`).
* **Mock & API Layer**: Axios client with mock fallback adapter (`resources/js/mocks/mockAdapter.ts`).
* **Multi-Tenancy**: Explicit `organization_id` tenancy scoping across routes, queries, and store lookups.
