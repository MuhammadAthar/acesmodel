# Modelfy — AI-Powered Fashion Asset Generation Platform

Modelfy is a full-stack SaaS platform that allows fashion brands to generate professional AI-rendered product images, marketing campaigns, lookbooks, and social media content — without physical photoshoots.

Upload a garment, define your brand identity, choose a model persona, and let the AI generate studio-quality visuals in seconds.

---

## Purpose

Traditional fashion photography is expensive, time-consuming, and limited in diversity. Modelfy solves this by:

- **Eliminating photoshoot costs** — Generate product images on diverse AI model personas instantly
- **Automating brand storytelling** — AI analyzes your brand DNA and tailors every generated asset to match your aesthetic
- **Scaling content production** — Generate social media tiles, banners, lifestyle shots, and lookbooks from a single garment upload
- **Democratizing diversity** — Showcase garments on models of any age, ethnicity, body type, and skin tone

---

## How It Works

1. **Set up your Brand** — Upload your logo, define your color palette, photography style preferences, and let the AI perform a Brand DNA analysis to extract your visual identity
2. **Upload Garments** — Upload garment images; the AI automatically removes backgrounds, detects category, fabric, colors, texture, season, and style tags
3. **Create a Campaign** — Group garments into a themed campaign with a creative brief and generation settings
4. **Choose Model Personas** — Select from a curated library of AI personas (diverse demographics, body types, ethnicities)
5. **Generate Assets** — The platform queues AI generation jobs and produces product shots, lifestyle images, social tiles, and banners
6. **Publish & Share** — Compile assets into a digital Lookbook, schedule social media posts, or download for direct use

---

## Tech Stack

### Frontend
| Technology | Purpose |
|---|---|
| Vue 3 (Composition API) | SPA framework |
| Pinia | Global state management |
| Vue Router 4 | Client-side routing |
| Tailwind CSS | Utility-first styling |
| Vite | Build tool & dev server |
| Axios | HTTP client |
| @vueuse/core | Vue composable utilities |

### Backend
| Technology | Purpose |
|---|---|
| Laravel 12 (PHP 8.2+) | REST API & business logic |
| Laravel Sanctum | API token authentication |
| Laravel Queue | Async AI job processing |
| Guzzle HTTP | External API communication |
| PHPUnit | Testing |
| Vite (Laravel) | Asset bundling |

### Infrastructure
- **AI Providers** — Configurable per-tenant AI provider keys (Replicate, OpenAI, etc.)
- **File Storage** — Laravel filesystem (local / S3-compatible)
- **Database** — MySQL / PostgreSQL / SQLite
- **Queue** — Database / Redis queue driver
- **Payments** — Integrated payment gateway with webhook support

---

## Features

- **Brand DNA Analysis** — AI extracts brand personality, aesthetics, and visual identity from uploaded assets
- **Garment Intelligence** — Auto-detects category, gender fit, season, colors, fabric, and style tags via AI
- **Model Persona Library** — Admin-curated diverse personas (gender, age, ethnicity, skin tone, body type, hair style)
- **Campaign Management** — Group garments, set briefs, and manage multi-asset generation runs
- **Asset Types** — Product shots, lifestyle images, social media tiles, banner ads
- **Virtual Try-On** — AI-powered garment fitting on model photos
- **Lookbooks** — Build public-shareable digital lookbooks from campaign assets
- **Social Media Content** — AI-generated captions, hashtags, and scheduled posts for Instagram, TikTok, Facebook, etc.
- **Credit System** — Pay-as-you-go credits with subscription plans (Starter, Growth, Agency)
- **Admin Panel** — Manage users, personas, AI configs, subscriptions, and platform analytics
- **Role-Based Access** — User and Super Admin roles with policy-enforced permissions

---

## Project Structure

```
modelfy/
├── backend/          # Laravel 12 REST API
│   ├── app/
│   │   ├── Http/Controllers/    # API controllers
│   │   ├── Jobs/                # Async AI generation jobs
│   │   ├── Models/              # Eloquent models
│   │   ├── Policies/            # Authorization policies
│   │   └── Services/
│   │       ├── AI/              # AI provider integrations
│   │       └── Payment/         # Payment gateway integrations
│   ├── routes/
│   │   └── api.php              # All API routes
│   └── database/migrations/     # Database schema
│
└── frontend/         # Vue 3 SPA
    └── src/
        ├── views/               # Page components
        ├── components/          # Reusable UI components
        ├── stores/              # Pinia state stores
        ├── router/              # Vue Router config
        └── layouts/             # App & Admin layouts
```

---

## Local Setup

### Prerequisites
- PHP 8.2+
- Composer
- Node.js 18+
- npm
- MySQL or PostgreSQL

### 1. Clone the repo

```bash
git clone https://github.com/MuhammadAthar/acesmodel.git
cd acesmodel
```

### 2. Backend setup

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
```

Edit `.env` with your database credentials and AI provider API keys:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=modelfy
DB_USERNAME=root
DB_PASSWORD=

# AI Provider
REPLICATE_API_TOKEN=your_key_here

# Storage
FILESYSTEM_DISK=local
```

```bash
php artisan migrate --seed
php artisan storage:link
```

### 3. Frontend setup

```bash
cd ../frontend
npm install
cp .env.example .env    # set VITE_API_BASE_URL=http://localhost:8000
```

### 4. Run the app

From the project root:

```bash
npm install
npm run dev
```

This concurrently starts the Laravel server, queue worker, and Vite dev server.

---

## Test Login Credentials

> Use these accounts to explore the platform without signing up.

### Super Admin
| Field | Value |
|---|---|
| Email | `admin@modelfy.com` |
| Password | `password` |
| Access | Full admin panel, user management, AI config, persona curation |

### Regular User (Brand Owner)
| Field | Value |
|---|---|
| Email | `demo@modelfy.com` |
| Password | `password` |
| Access | Studio, campaigns, garment uploads, asset generation, billing |

> Seeded accounts are created by `database/seeders/DatabaseSeeder.php`. Run `php artisan db:seed` to reset.

---

## API Overview

All API routes are prefixed with `/api`.

| Method | Endpoint | Description |
|---|---|---|
| POST | `/register` | Create account |
| POST | `/login` | Authenticate and get token |
| GET | `/me` | Current user profile |
| GET/POST | `/brands` | List / create brands |
| POST | `/brands/{id}/analyze-dna` | Trigger Brand DNA AI analysis |
| GET/POST | `/garments` | List / upload garments |
| POST | `/garments/{id}/analyze` | Trigger garment AI analysis |
| GET/POST | `/campaigns` | List / create campaigns |
| POST | `/campaigns/{id}/generate` | Start asset generation |
| GET | `/assets` | Browse generated asset library |
| GET | `/public/model-personas` | Public model persona listing |
| GET | `/lookbook/{slug}` | Public lookbook viewer |

Full route reference: [`backend/routes/api.php`](backend/routes/api.php)

---

## Subscription Plans

| Plan | Credits | Use Case |
|---|---|---|
| Starter | 100/mo | Indie designers, small brands |
| Growth | 500/mo | Growing fashion labels |
| Agency | Unlimited | Agencies managing multiple brands |

Each AI asset generation deducts credits from the user's balance. Credits can also be purchased as one-off top-ups.

---

## License

This project is proprietary software developed by **Aces Ads**. All rights reserved.
