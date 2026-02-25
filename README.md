# Adaptive Maths Practice

A browser-based adaptive maths practice tool for a single student. The student works through five Grade 5–6 multiplication word problems per session. Difficulty adjusts automatically after each answer and an AI generates personalised feedback.

> **Other documents**  
> Product requirements → [PRD.md](PRD.md)  
> Technical decisions & schema → [ARCHITECTURE.md](ARCHITECTURE.md)  
> Trade-offs & production gaps → [TRADEOFFS.md](TRADEOFFS.md)

---

## Stack

| Layer | Technology |
|---|---|
| Frontend | React 18 + TypeScript, Vite 6, Tailwind CSS v4 |
| Backend | Laravel 12, PHP 8.4, nginx + php-fpm |
| Database | MySQL 8 |
| AI | Ollama (`llama3.2`) — runs locally in Docker |
| Container | Docker Compose |

---

## Prerequisites

| Requirement | Version |
|---|---|
| Docker Desktop | 4.x or newer (with Compose v2 included) |
| Git | any recent version |

No local PHP, Node, or Python installation is required. Everything runs inside containers.

---

## First-time setup

### 1. Clone the repository

```bash
git clone https://github.com/singh-ip/adaptive-maths-practice.git
cd adaptive-maths-practice
```

### 2. Create environment files

Three `.env` files are required — one at the repository root, one inside `backend/` and one inside `frontend/`.

**macOS / Linux**

```bash
# Root-level .env — used by docker-compose.yml for MySQL init variables
cp backend/.env.example .env

# Backend .env — read by Laravel at runtime
cp backend/.env.example backend/.env

# Frontend .env — read by React at runtime
cp frontend/.env.example frontend/.env
```

**Windows (PowerShell)**

```powershell
# Root-level .env — used by docker-compose.yml for MySQL init variables
Copy-Item backend\.env.example .env

# Backend .env — read by Laravel at runtime
Copy-Item backend\.env.example backend\.env

# Frontend .env — read by React at runtime
Copy-Item frontend\.env.example frontend\.env
```

**Windows (Command Prompt)**

```cmd
:: Root-level .env — used by docker-compose.yml for MySQL init variables
copy backend\.env.example .env

:: Backend .env — read by Laravel at runtime
copy backend\.env.example backend\.env

:: Frontend .env — read by React at runtime
copy frontend\.env.example frontend\.env
```

Edit `backend/.env` and set at minimum:

| Variable | What to do |
|---|---|
| `APP_KEY` | Leave blank — generated in step 4 |
| `APP_DEBUG` | Set `true` for local development |
| `DB_PASSWORD` | Match the password in the root `.env` `MYSQL_ROOT_PASSWORD` |
| `CORS_ALLOWED_ORIGINS` | `http://localhost:5173,http://127.0.0.1:5173` (Vite default) |
| `OLLAMA_MODEL` | `llama3.2` (default) |

For the frontend, the copied `.env` requires no changes locally:

```
# VITE_API_BASE_URL=http://localhost:8000  ← default, no change needed locally
```

### 3. Build and start the stack

```bash
docker compose up --build -d
```

This starts four services: `frontend` (Vite, port 5173), `php`+`nginx` (Laravel, port 8000), `mysql` (port 3306), `ollama` (port 11434).

### 4. Generate the Laravel application key (one-time)

```bash
docker compose exec php php artisan key:generate
```

### 5. Run database migrations (one-time)

```bash
docker compose exec php php artisan migrate
```

### 6. Pull the AI model

> **Read this section before proceeding — the model is ~2 GB and requires patience.**

```bash
docker compose exec ollama ollama pull llama3.2
```

This downloads `llama3.2` into the Ollama container's model volume. The download can take several minutes depending on network speed. **Do not interrupt it** — if the download is cut short (network drop, `Ctrl+C`, container restart), Ollama leaves a partial file that appears complete but produces blank or garbled responses.

**Symptoms of a partial pull:** the `feedback` field in API responses is empty, or question generation silently falls back to simple equations.

**Fix:** run `ollama pull llama3.2` again. The command is idempotent and will resume/re-download any missing chunks. Once it reports `success` the model is fully available.

The model name is controlled by `OLLAMA_MODEL` in `backend/.env`. To use a different model:

```bash
docker compose exec ollama ollama pull llama3.1   # or any other supported model
# then update OLLAMA_MODEL=llama3.1 in backend/.env
```

### 7. Open the app

Navigate to **http://localhost:5173** in your browser.

---

## Day-to-day commands

```bash
# Start all services (after first-time setup)
docker compose up -d

# Stop all services (data is preserved)
docker compose down

# View logs
docker compose logs -f
docker compose logs -f php       # backend only
docker compose logs -f frontend  # frontend only

# Run backend tests
docker compose exec php php artisan test

# Run PHPStan static analysis (level 6)
docker compose exec php composer check:stan

# Auto-fix code style (Laravel Pint)
docker compose exec php php vendor/bin/pint

# Open a shell in the backend container
docker compose exec php sh

# Full reset — deletes DB data and model volumes
docker compose down -v
```

---

## Project structure

```
adaptive-maths-practice/
├── docker-compose.yml          # Full stack: frontend + backend + mysql + ollama
├── .env                        # Root-level env (MySQL init vars) — not committed
├── PRD.md                      # Product requirements document
├── ARCHITECTURE.md             # System design and technical decisions
├── TRADEOFFS.md                # Production gaps and scaling notes
│
├── backend/                    # Laravel API
│   ├── .env.example            # Template — copy to .env AND to root .env
│   ├── app/
│   │   ├── AI/                 # Ollama HTTP client, parsers, prompts, fallbacks
│   │   ├── Contracts/          # QuestionGeneratorContract, FeedbackGeneratorContract
│   │   ├── DTOs/               # Typed response objects (SessionDto, SubmitAnswerDto, …)
│   │   ├── Enums/              # SessionStatus backed enum
│   │   ├── Http/Controllers/   # SessionController, AnswerController
│   │   ├── Http/Requests/      # FormRequest validation classes
│   │   ├── Models/             # Session, Question, Answer (Eloquent)
│   │   ├── Queries/            # Database query classes
│   │   └── Services/           # Business logic (AdaptiveAlgorithm, SessionService, …)
│   ├── captainhook.json        # Git hooks config (pre-commit, pre-push) — currently disabled
│   ├── phpstan.neon            # Static analysis config (level 6)
│   └── tests/Unit/             # AdaptiveAlgorithmTest, AnswerValidatorTest
│
└── frontend/                   # React SPA
    ├── .env.example            # Template — copy to .env
    ├── src/app/
    │   ├── App.tsx             # Provider root (ThemeProvider + SessionProvider)
    │   ├── Home.tsx            # Screen router
    │   ├── components/         # Presentational components + ui/ primitives
    │   ├── context/            # appReducer.ts, SessionContext.tsx
    │   ├── hooks/              # usePracticeSession, useSubmitAnswer, useNextQuestion
    │   ├── services/           # apiClient.ts — all fetch + raw→domain mapping
    │   ├── types/              # api.ts — Raw* shapes + domain types
    │   ├── constants/          # messages.ts — all user-facing copy
    │   └── utils/              # error.ts — isAbortError, extractErrorMessage
    └── ARCHITECTURE.md         # Frontend-specific architecture detail
```

---

## UI Design

The initial UI design was created using **Figma AI** (Make), which generated the component layout and visual structure. The generated scaffold was then cleaned and rebuilt in code: all unused Figma-generated components (~32 shadcn/ui primitives), mock API files, and placeholder dependencies were removed to match the actual product scope.

---

## Troubleshooting

**MySQL healthcheck stays "starting"** — wait 30–60 s; MySQL init on a cold volume is slow.
Check: `docker compose logs mysql`

**`APP_KEY` missing error** — run `docker compose exec php php artisan key:generate`

**Config changes not reflected** — run `docker compose exec php php artisan config:clear`

**Ollama responds but feedback is empty** — the model was not fully pulled. Re-run `docker compose exec ollama ollama pull llama3.2`.

**First question takes 30–60 s** — normal on first request; the model is loading into memory from disk. Subsequent questions in the same session are faster.

**Hot-reload not working in frontend** — the Vite config uses polling (`usePolling: true`) because Windows NTFS bind-mounts cannot use inotify inside Linux containers. If polling stops, restart the frontend container: `docker compose restart frontend`.
