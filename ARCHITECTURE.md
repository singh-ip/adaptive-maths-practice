# Architecture

> **Related documents**  
> Product requirements → [PRD.md](PRD.md)  
> Setup & running → [README.md](README.md)  
> Trade-offs → [TRADEOFFS.md](TRADEOFFS.md)  
> Frontend detail → [frontend/ARCHITECTURE.md](frontend/ARCHITECTURE.md)

---

## 1. System topology

```
┌─────────────────────────────────────────────────────────────┐
│  Browser                                                    │
│  React SPA — Vite dev server (port 5173)                    │
└────────────────────┬────────────────────────────────────────┘
                     │ HTTP/JSON
                     ▼
┌─────────────────────────────────────────────────────────────┐
│  nginx (port 8000) → php-fpm → Laravel 12 / PHP 8.4        │
│                                                             │
│  Routes:                                                    │
│    POST   /api/practice-sessions                            │
│    POST   /api/practice-sessions/{id}/answers               │
│    GET    /api/practice-sessions/{id}                       │
└──────────┬──────────────────────────┬───────────────────────┘
           │                          │
           ▼                          ▼
┌─────────────────┐       ┌─────────────────────────────────┐
│  MySQL 8        │       │  Ollama (port 11434)             │
│  (port 3306)    │       │  model: llama3.2 (~2 GB)        │
│  3 tables       │       │  question generation + feedback  │
└─────────────────┘       └─────────────────────────────────┘
```

All four services run via `docker-compose.yml` at the repository root.

---

## 2. Database schema

### `sessions`

| Column | Type | Notes |
|---|---|---|
| `id` | `bigint unsigned` PK | Auto-increment |
| `status` | `varchar` | `'active'` or `'completed'` (default: `'active'`) |
| `current_difficulty` | `int` | 1–10; starts at 5 |
| `questions_count` | `int` | 0–5; incremented per answer |
| `created_at` | `timestamp` | |
| `updated_at` | `timestamp` | |

### `questions`

| Column | Type | Notes |
|---|---|---|
| `id` | `bigint unsigned` PK | |
| `session_id` | `bigint unsigned` FK → `sessions.id` | |
| `question_number` | `int` | 1–5 within session |
| `question` | `text` | Full word-problem text |
| `correct_answer` | `int` | |
| `difficulty` | `int` | Difficulty when question was generated |
| `created_at` | `timestamp` | |
| `updated_at` | `timestamp` | |

### `answers`

| Column | Type | Notes |
|---|---|---|
| `id` | `bigint unsigned` PK | |
| `session_id` | `bigint unsigned` FK → `sessions.id` | |
| `question_id` | `bigint unsigned` FK → `questions.id` | |
| `answer` | `int` | Student's submitted answer |
| `is_correct` | `boolean` | |
| `feedback` | `text` nullable | AI-generated explanation |
| `created_at` | `timestamp` | |
| `updated_at` | `timestamp` | |

---

## 3. Adaptive algorithm

Implemented in `backend/app/Services/AdaptiveAlgorithm.php`. The entire logic is in one class to make it easy to swap for an IRT (Item Response Theory) model later without touching any other layer.

```
Session starts              → difficulty = 5   (START_DIFFICULTY)
Student answers correctly   → difficulty = min(current + 1, 10)
Student answers incorrectly → difficulty = max(current - 1, 1)
After 5 answers             → session marked 'completed'
```

Constants are defined once:

```php
public const MIN_DIFFICULTY  = 1;
public const MAX_DIFFICULTY  = 10;
public const TOTAL_QUESTIONS = 5;
public const START_DIFFICULTY = 5;
```

`TOTAL_QUESTIONS = 5` is the single source of truth. The frontend `TOTAL_QUESTIONS = 5` constant in `Home.tsx` is a display-only value; the session-complete signal always comes from the backend `session_complete: true` field in the API response.

The difficulty bands shown in the UI are derived purely on the frontend for display purposes:

| Internal scale | Label | Colour |
|---|---|---|
| 1–4 | Easy | Green |
| 5–7 | Medium | Amber |
| 8–10 | Hard | Red |

---

## 4. Backend layer design

### 4.1 Controllers — table-context separation of concerns

Two controllers are used, each aligned with one primary database table:

| Controller | Routes | Table context |
|---|---|---|
| `SessionController` | `POST /practice-sessions`, `GET /practice-sessions/{id}` | `sessions` |
| `AnswerController` | `POST /practice-sessions/{id}/answers` | `answers` |

A **single-controller pattern** (one `PracticeController` handling all three routes) was considered and rejected. It would conflate session-lifecycle concerns with answer-submission concerns in one class, making each method's responsibility less obvious. The two-controller layout maps cleanly to the two core nouns in the domain.

This can scale in future: a resource-controller pattern (`SessionController` + `AnswerController` as nested resource) or a single-action invokable controller per route (`StartSession`, `SubmitAnswer`, `GetSummary`) would both work with zero schema changes.

### 4.2 DTOs — explicit contracts for co-developers

Every service method returns a typed, `readonly` DTO rather than a raw array:

| DTO | Created by | Returned to |
|---|---|---|
| `SessionDto` | `SessionService::create()` | `SessionController::store()` |
| `SubmitAnswerDto` | `SubmitAnswerService::handle()` | `AnswerController::store()` |
| `SessionSummaryDto` | `SessionSummaryService::build()` | `SessionController::show()` |
| `QuestionDetailDto` | `SessionSummaryService` (internal) | embedded in `SessionSummaryDto` |

**Why DTOs instead of arrays:**

- **Discoverability:** A co-developer or future engineer reads the DTO class to know the exact shape of data — no guessing array keys from documentation or grepping across files.
- **Type safety:** PHPStan level 6 verifies every property access at analysis time.
- **Stability:** If an internal query changes, the DTO surface is unchanged. Callers never break.
- All DTOs are `final readonly` — they are value objects, never mutated after construction.

### 4.3 Contracts — swappable AI providers

Both AI interactions are hidden behind dedicated interfaces bound in `AppServiceProvider`:

```php
// QuestionGeneratorContract
public function generate(int $difficulty, array $previousQuestions = []): array;

// FeedbackGeneratorContract
public function explain(string $question, int $correctAnswer, int $studentAnswer): string;
```

`OllamaClient.php` is the current implementation. To switch to OpenAI, Gemini, or any other provider, write a new implementation class and change the binding in `AppServiceProvider` — no controller, service, or route code changes.

Both contracts include fallback implementations (`Fallbacks/QuestionFallback.php`, `Fallbacks/FeedbackFallback.php`) that return deterministic results without any LLM, used when the Ollama call fails.

### 4.4 Enums — single source of truth for status values

`SessionStatus` is a PHP-backed enum (`active`, `completed`). It appears in:

- `database/migrations/` — column default
- `SessionSummaryDto::$status` — typed property, never a plain `string`
- `SubmitAnswerService` — completion check

This guarantees the status string can never silently drift. The equivalent is applied on the frontend: `SessionStatus` is a TypeScript literal union (`'not-started' | 'in-progress' | 'completed'`) cast at the API boundary in `apiClient.ts`.

### 4.5 Validation messages and the lang file

Request validation rules are in `app/Http/Requests/` (FormRequest classes). User-facing validation error messages follow Laravel's standard `lang/en/validation.php` location. Keeping copy in the lang file means it can be localised in future without touching any controller or service — the file is already in place, it just has English only.

---

## 5. Frontend state architecture

### 5.1 Pattern

The frontend follows a **Flux-like unidirectional data flow**:

```
User interaction
      │
      ▼
Custom hook  (useSubmitAnswer / useNextQuestion / usePracticeSession)
      │  calls apiClient, then dispatches
      ▼
appReducer  (pure function — AppState × AppAction → AppState)
      │
      ▼
SessionContext  (React Context — provides state to component tree)
      │
      ▼
Components  (read state, call hooks — zero local business logic)
```

### 5.2 Why `useReducer` instead of scattered `useState`

Multiple interdependent fields (`isLoading`, `error`, `currentQuestion`, `status`) must update atomically. With `useState` each field is set separately, creating the risk of intermediate renders where `isLoading = false` but `currentQuestion = null`. `useReducer` replaces the entire state object in one synchronous step — every transition is atomic and named.

### 5.3 Why React Context + custom hooks

Context was introduced for two specific reasons:

1. **Eliminate prop drilling.** Before the refactor, `QuestionDisplay` received 9 props. After, it receives 3 (`question`, `onRestart`, `totalQuestions`). The hooks (`useSubmitAnswer`, `useNextQuestion`) call `useSessionContext()` to get `dispatch` and `startRequest` directly — they do not need to be wired through the component tree.

2. **Keep the `AbortController` ref centralised.** `startRequest()` lives in `SessionContext`. Before every API call it aborts the previous in-flight request and creates a new `AbortController`. This prevents a race condition where a slow summary fetch resolves into a freshly restarted session after "Start Again" is clicked.

**Why not Redux or Zustand?** The state is a single linear session flow — one screen active at a time, no shared state across sub-trees, no background synchronisation. An external store would add a dependency and an abstraction layer with no benefit at this scope.

### 5.4 Session state machine

`AppState.status` acts as a finite state machine:

```
'landing'
    │  START_SESSION_SUCCESS
    ▼
'in-progress'
    │  LOAD_SUMMARY_SUCCESS
    ▼
'completed'

Any state → 'error'    on *_ERROR actions
Any state → 'landing'  on RESTART
```

It is structurally impossible to render `CompletionScreen` without a `summary` object because the only path to `'completed'` is `LOAD_SUMMARY_SUCCESS`, which always carries a `summary` payload.

### 5.5 Two-tier type system

| Tier | Naming | Purpose |
|---|---|---|
| Raw | `RawSessionStart`, `RawAnswerResponse`, `RawSessionSummary` | Mirror of the backend JSON. Only `apiClient.ts` reads these. |
| Domain | `Question`, `AnswerResponse`, `SessionSummary` | Camelcase, typed enums, nullable-safe. What components work with. |

Mapping from Raw → Domain happens in `apiClient.ts` mapper functions. If the backend renames a field, only `types/api.ts` and `apiClient.ts` change — no component file is touched.

### 5.6 Request cancellation

`SessionContext` holds an `abortRef: useRef<AbortController | null>`. The `startRequest()` function aborts the previous controller before creating a new one. The `AbortSignal` is threaded through every `apiClient` method into `fetch`. `AbortError` is silently swallowed in every catch block — it is intentional cancellation, not a failure.

### 5.7 User-facing copy — constants, not inline strings

All UI copy (feedback messages, error labels, developer warnings) is centralised in `src/app/constants/messages.ts`. The service layer (`apiClient.ts`) does not own UI copy; it imports `FEEDBACK_MESSAGES` from the constants module. This enforces separation of concerns between the data-fetching layer and the presentation layer.

---

## 6. Code quality tooling

### PHPStan (static analysis)

`phpstan.neon` is configured at **level 6** using Larastan (Laravel-aware extension). Level 6 enforces return-type strictness and null-safety beyond the level 5 baseline. The config file documents why the threshold is set at 6 and warns against increasing it without first auditing Eloquent magic-method false positives.

Run manually inside the container:

```bash
docker compose exec php composer check:stan
```

### CaptainHook (`captainhook.json`)

CaptainHook is configured to automate code quality checks as Git hooks:

| Hook | Command |
|---|---|
| `pre-commit` | Laravel Pint (style check) + PHPStan (static analysis) |
| `pre-push` | PHPUnit (unit tests) |

**All hooks are currently disabled** (`"enabled": false`). The repository is a monorepo — `backend/` and `frontend/` share the same `.git` root. CaptainHook's `git-directory` is set to `../.git` relative to `backend/`, which means hooks would need to be installed and run in the correct subdirectory context. This is error-prone when contributors clone the full monorepo.

**When to enable:** extract `backend/` into its own standalone repository. At that point, run `vendor/bin/captainhook install` inside `backend/` and set all `"enabled": true`. The configuration is already correct and ready to activate.

Until then, run checks manually:

```bash
docker compose exec php composer check:stan   # PHPStan
docker compose exec php php vendor/bin/pint   # Pint
docker compose exec php php artisan test      # PHPUnit
```

---

## 7. Previous-question skip logic

When the backend requests the next question from the AI, it passes the full list of question texts already used in the current session:

```php
// QuestionGeneratorContract
public function generate(int $difficulty, array $previousQuestions = []): array;
```

The `previousQuestions` array is included in the Ollama prompt so the model does not repeat a problem already seen in the session. On the frontend, `AppState.pastQuestions: string[]` accumulates question text on every `LOAD_NEXT_QUESTION` dispatch and is forwarded as `past_questions` in the `submitAnswer` API request body.

---

## 8. UI design

The initial component layout and visual structure were designed using **Figma AI (Make)**, which generated an initial React scaffold. The generated output was then substantially reworked: all unused Figma-generated components (~32 shadcn/ui primitives), the mock API file, and approximately 40 unused npm dependencies were removed. The remaining 8 UI primitives (`alert`, `button`, `card`, `input`, `label`, `progress`, `skeleton`, `utils.ts`) match exactly what the application uses.

The colour system, typographic scale, and spacing tokens are defined as CSS custom properties in `frontend/src/styles/theme.css` and consumed via Tailwind utility classes. Light/dark mode is handled by `next-themes`.
