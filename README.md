# EasyAI — Self-Hosted Multi-Tenant AI Workspace Platform

> A Laravel-powered AI workspace with ChatGPT-style UX, backed by Ollama.
> Built for teams and organizations who want private, self-hosted AI.

![Laravel](https://img.shields.io/badge/Laravel-10.x-FF2D20?style=flat&logo=laravel)
![Vue](https://img.shields.io/badge/Vue-3.x-4FC08D?style=flat&logo=vue.js)
![Inertia](https://img.shields.io/badge/Inertia.js-1.x-7C3AED?style=flat)
![Ollama](https://img.shields.io/badge/Ollama-Local_AI-000000?style=flat)
![License](https://img.shields.io/badge/License-MIT-blue?style=flat)

---

## What is EasyAI?

EasyAI is a **self-hosted, multi-tenant AI workspace platform** that gives your
organization a private ChatGPT-style experience powered by local AI models via
Ollama. No data leaves your server.

**Main app:** `easyai.local` (or your domain)
**Admin panel:** `pis.easyai.local` (or admin subdomain)

---

## Features

### AI & Chat
- ChatGPT-style chat interface
- Project-based workspaces with isolated chat threads
- AI Personas per project (custom system prompts)
- Short-term memory (full chat history context)
- Long-term memory (auto-summarized project context)
- Prompt templates library (personal + shared)
- Multiple Ollama model support (llama3, mistral, codellama, etc.)
- Chat export — PDF (mPDF) and Markdown

### Multi-Tenancy
- Full tenant isolation (each org has own data)
- Per-tenant subscription plans with token quotas
- Monthly token usage tracking and enforcement
- Tenant admin and member roles

### Billing
- **COD** — Manual payment with admin approval workflow
- **SSLCommerz** — Bangladesh payment gateway (bKash, Nagad, cards, net banking)
- **Stripe** — International credit/debit cards
- mPDF invoice generation per payment
- Subscription management

### API (v1)
- Full REST API with Sanctum token authentication
- All features available via API
- Designed for mobile app and external integrations
- Versioned: `/api/v1/`

### Admin Panel (`pis.easyai.local`)
- Manage all tenants (view, suspend, change plan)
- COD payment approval workflow
- Usage analytics across all tenants
- Plan management
- Lucide icons throughout

### Tech
- Lucide Vue icons in all UI components
- Queue-based AI processing (no timeouts)
- Laravel Horizon for queue monitoring
- Dark theme tenant UI, light theme admin panel

---

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | Laravel 10 |
| Frontend | Vue 3 (Composition API) + Inertia.js |
| Build Tool | Vite |
| CSS | Tailwind CSS |
| Icons | Lucide Vue Next |
| Database | MySQL 8 |
| Cache / Queue | Redis + Laravel Horizon |
| AI Engine | Ollama (self-hosted) |
| Auth | Laravel Sanctum |
| PDF | mPDF |
| Billing | Laravel Cashier (Stripe) + SSLCommerz + COD |
| Roles | Spatie Laravel Permission |

---

## Requirements

- PHP 8.1 or 8.2
- Composer
- Node.js 18+
- MySQL 8
- Redis
- Ollama (separate server or local)
- Git

---

## Local Development Setup

### 1. Clone the repository

```bash
git clone https://github.com/YOUR_USERNAME/easyai.git
cd easyai
```

### 2. Install dependencies

```bash
composer install
npm install
```

### 3. Configure environment

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env`:

```env
APP_NAME="EasyAI"
APP_URL=http://easyai.local

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=easyai
DB_USERNAME=root
DB_PASSWORD=

CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

OLLAMA_URL=http://127.0.0.1:11434
OLLAMA_MODEL=llama3

# Stripe
STRIPE_KEY=pk_test_xxx
STRIPE_SECRET=sk_test_xxx
STRIPE_WEBHOOK_SECRET=whsec_xxx

# SSLCommerz
SSLCZ_STORE_ID=your_store_id
SSLCZ_STORE_PASSWD=your_store_password
SSLCZ_TESTMODE=true

# COD
COD_ENABLED=true
```

### 4. Setup database

```bash
php artisan migrate
php artisan db:seed
```

This seeds: 3 plans, 1 superadmin, test tenants.

**Default superadmin:** `admin@easyai.local` / `password`

### 5. Build assets

```bash
npm run dev
```

### 6. Start queue worker

```bash
# In a separate terminal
php artisan queue:work redis
```

### 7. Setup Ollama

```bash
# Install from https://ollama.com
ollama pull llama3
```

### 8. Configure virtual hosts (XAMPP / local)

**Windows hosts file** (`C:\Windows\System32\drivers\etc\hosts`):

```
127.0.0.1    easyai.local
127.0.0.1    pis.easyai.local
```

**Apache vhosts** (`httpd-vhosts.conf`):

```apache
<VirtualHost *:80>
    ServerName easyai.local
    DocumentRoot "C:/xampp/htdocs/easyai/public"
    <Directory "C:/xampp/htdocs/easyai/public">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>

<VirtualHost *:80>
    ServerName pis.easyai.local
    DocumentRoot "C:/xampp/htdocs/easyai/public"
    <Directory "C:/xampp/htdocs/easyai/public">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

---

## Usage

| URL | Description |
|---|---|
| `http://easyai.local` | Tenant app (login / register) |
| `http://easyai.local/horizon` | Queue monitor (dev) |
| `http://pis.easyai.local` | Super admin panel |
| `http://localhost/phpmyadmin` | Database (XAMPP) |

---

## API Documentation

Base URL: `http://easyai.local/api/v1`

All protected routes require: `Authorization: Bearer {token}`

### Authentication

```
POST /api/v1/auth/login      → returns token + user
POST /api/v1/auth/register   → returns token + user
POST /api/v1/auth/logout     → revokes token
GET  /api/v1/auth/me         → current user + tenant
```

### Projects

```
GET    /api/v1/projects
POST   /api/v1/projects
GET    /api/v1/projects/{id}
PUT    /api/v1/projects/{id}
DELETE /api/v1/projects/{id}
```

### Chats

```
GET    /api/v1/projects/{project}/chats
POST   /api/v1/projects/{project}/chats
GET    /api/v1/projects/{project}/chats/{chat}
DELETE /api/v1/projects/{project}/chats/{chat}
POST   /api/v1/projects/{project}/chats/{chat}/close
```

### Messages

```
GET    /api/v1/projects/{project}/chats/{chat}/messages
POST   /api/v1/projects/{project}/chats/{chat}/messages
GET    /api/v1/projects/{project}/chats/{chat}/messages/status
```

### Usage & Billing

```
GET    /api/v1/tenant/usage
GET    /api/v1/billing/plans
GET    /api/v1/billing/current-plan
GET    /api/v1/billing/invoices
GET    /api/v1/billing/invoices/{id}/download
```

### Response Format

All API responses follow this structure:

```json
{
  "success": true,
  "message": "OK",
  "data": {},
  "meta": {
    "current_page": 1,
    "total": 10,
    "per_page": 15
  }
}
```

---

## Supported AI Models (Ollama)

| Model | Use Case | RAM Required |
|---|---|---|
| llama3 | General purpose (default) | 8GB |
| mistral | Fast, lightweight | 4GB |
| codellama | Code generation | 8GB |
| llama3:70b | High quality (large) | 40GB |

Pull a model:

```bash
ollama pull llama3
ollama pull mistral
ollama pull codellama
```

---

## Subscription Plans

| Plan | Tokens / Month | Price |
|---|---|---|
| Starter | 500,000 | $29 / month |
| Pro | 2,000,000 | $79 / month |
| Enterprise | 10,000,000 | $199 / month |

Token quota resets automatically on the 1st of every month.

---

## Payment Methods

- **COD** — Manual payment. Tenant requests → admin approves → activated.
- **SSLCommerz** — bKash, Nagad, Rocket, local bank cards (Bangladesh).
- **Stripe** — International credit/debit cards.

All payments generate a mPDF invoice downloadable from the billing page.

---

## Project Structure

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Api/V1/          ← API controllers
│   │   ├── Admin/           ← Admin panel controllers
│   │   ├── AuthController.php
│   │   ├── ProjectController.php
│   │   ├── ChatController.php
│   │   ├── MessageController.php
│   │   ├── BillingController.php
│   │   └── ExportController.php
│   └── Middleware/
│       ├── TenantMiddleware.php
│       └── SuperAdminMiddleware.php
├── Jobs/
│   ├── SendMessageJob.php
│   ├── SummarizeChatJob.php
│   └── ResetMonthlyQuotaJob.php
├── Models/
│   ├── Tenant.php
│   ├── Plan.php
│   ├── Project.php
│   ├── Chat.php
│   ├── Message.php
│   ├── UsageLog.php
│   ├── Payment.php
│   ├── Subscription.php
│   └── PromptTemplate.php
└── Services/
    ├── OllamaService.php
    ├── QuotaService.php
    ├── TokenCounterService.php
    ├── MemoryService.php
    ├── BillingService.php
    └── InvoiceService.php

resources/js/
├── Layouts/
│   ├── AppLayout.vue        ← Tenant dark layout
│   └── AdminLayout.vue      ← Admin light layout
├── Components/
│   ├── Sidebar.vue
│   ├── TokenBar.vue
│   └── UpgradeModal.vue
└── Pages/
    ├── Auth/
    ├── Dashboard.vue
    ├── Projects/
    ├── Chats/
    ├── Templates/
    ├── Billing/
    ├── Usage/
    └── Admin/
```

---

## Deployment

### cPanel (Shared Hosting)

- Set PHP 8.2 in PHP Selector
- Use `database` queue driver (no Redis on shared hosting)
- Use cron job every minute for queue and scheduler
- Ollama must run on a separate VPS

```
* * * * * cd /path/to/easyai && php artisan queue:work --stop-when-empty
* * * * * cd /path/to/easyai && php artisan schedule:run
```

### VPS (Ubuntu 22.04) — Recommended for Production

```bash
# Install PHP 8.2, MySQL, Redis, Nginx, Composer, Node 18
# Clone repo, composer install, npm build
# Configure Nginx, SSL (Let's Encrypt)
# Supervisor for queue workers + Horizon
# Cron for scheduler
```

Full VPS setup instructions available in `/docs/vps-setup.md`

---

## Environment Variables Reference

```env
# App
APP_NAME=EasyAI
APP_URL=https://yourdomain.com
APP_ENV=production
APP_DEBUG=false

# Database
DB_CONNECTION=mysql
DB_DATABASE=easyai
DB_USERNAME=easyai_user
DB_PASSWORD=strongpassword

# Redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

# Ollama
OLLAMA_URL=http://OLLAMA_VPS_IP:11434
OLLAMA_MODEL=llama3

# Stripe
STRIPE_KEY=pk_live_xxx
STRIPE_SECRET=sk_live_xxx
STRIPE_WEBHOOK_SECRET=whsec_xxx

# SSLCommerz
SSLCZ_STORE_ID=your_store_id
SSLCZ_STORE_PASSWD=your_password
SSLCZ_TESTMODE=false

# COD
COD_ENABLED=true
```

---

## Security

- All data isolated by `tenant_id` — enforced in middleware, never trusted from user input
- Ollama server port (11434) blocked from public access (firewall rules)
- Sanctum token auth on all API routes
- Rate limiting per user
- Input validation with max 4000 character prompt limit
- HTTPS enforced in production

---

## Roadmap

- [ ] Streaming responses (SSE)
- [ ] Voice input (Whisper via Ollama)
- [ ] File upload + AI analysis (PDF, DOCX, CSV)
- [ ] RAG knowledge base per project
- [ ] MIS analytics dashboard
- [ ] Mobile app (Flutter / React Native using REST API)
- [ ] AI Agents / Workflow automation
- [ ] White-label per tenant
- [ ] Multi-node Ollama cluster

---

## Contributing

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/your-feature`
3. Commit: `git commit -m "feat: your feature description"`
4. Push: `git push origin feature/your-feature`
5. Open a Pull Request

---

## License

MIT License. See [LICENSE](LICENSE) file for details.

---

## Credits

Built with:
- [Laravel](https://laravel.com)
- [Vue.js](https://vuejs.org)
- [Inertia.js](https://inertiajs.com)
- [Ollama](https://ollama.com)
- [Lucide Icons](https://lucide.dev)
- [mPDF](https://mpdf.github.io)
- [Tailwind CSS](https://tailwindcss.com)
- [Stripe](https://stripe.com)
- [SSLCommerz](https://www.sslcommerz.com)

---

*EasyAI — Your private AI workspace. Your data stays yours.*
