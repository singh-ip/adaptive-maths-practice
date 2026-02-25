# Trade-offs, Production Gaps & Scaling Notes

> **Related documents**  
> Product requirements → [PRD.md](PRD.md)  
> Setup & running → [README.md](README.md)  
> Technical decisions → [ARCHITECTURE.md](ARCHITECTURE.md)

This document is written from the perspective of a senior engineer handing the codebase to a team. It is honest about what was deliberately simplified for the POC and what would need to change before the product could be considered production-ready.

---

## 1. What we'd improve with more time

### Backend

| Item | Current state | Improvement |
|---|---|---|
| Adaptive algorithm | Simple ±1 per answer, fixed 1–10 range | Replace with Item Response Theory (IRT) — difficulty converges to the student's actual ability rather than drifting linearly |
| Question diversity | AI generates questions on demand with no caching | Maintain a seeded question bank in the database; AI generates offline or at low-load times. Reduces per-request latency from ~5 s to <100 ms |
| AI prompt quality | Prompt engineering done once; no evaluation loop | Add a small eval harness that scores AI outputs for grade-appropriateness and correctness before promoting prompt changes |
| Unit test coverage | `AdaptiveAlgorithmTest` and `AnswerValidatorTest` only | Add service-layer tests (`SessionService`, `SubmitAnswerService`), HTTP feature tests for all three endpoints, and a contract test for `OllamaClient` against a mock Ollama |
| Error taxonomy | All AI failures fall back silently | Distinguish transient errors (network timeout → retry) from permanent errors (malformed model output → structured fallback) |

### Frontend

| Item | Current state | Improvement |
|---|---|---|
| Test coverage | No tests | Add unit tests for `appReducer` (all state transitions), `apiClient` mappers (raw→domain), and hook behaviour using `renderHook` from React Testing Library |
| Accessibility | `aria-live` on feedback panel; basic semantic markup | Full keyboard navigation audit, focus management between screens (move focus to H1 on screen transition), colour contrast ratio check against WCAG AA |
| Offline resilience | Network errors show a toast or error screen | Detect offline state with `navigator.onLine` / `online` event; show informative message rather than a generic API error |
| Progress persistence | Session state is lost on page refresh | Store `sessionId` in `sessionStorage`; on reload, attempt to resume the session from the API if it is still `active` |
| Animation polish | Framer Motion used only in `MathLoader` | Apply entrance animations to screen transitions to reduce perceived latency during AI feedback delay |

---

## 2. Production readiness gaps

| Gap | Impact | What is needed |
|---|---|---|
| **No authentication** | Any user can call any API endpoint; session IDs are sequential integers and guessable | JWT or session-cookie auth; session ownership check on every endpoint |
| **No rate limiting** | The API has no request throttling | Laravel's `throttle` middleware per IP and per user; Ollama calls separately rate-limited to prevent GPU/CPU saturation |
| **No input sanitisation beyond FormRequest** | `answer` field is validated as integer; `past_questions` array length is not bounded | Add max-length validation on `past_questions` items; add request-size limits in nginx config |
| **AI response not validated for safety** | Ollama output is passed through to the student without content filtering | Add output validation in `OllamaClient`; at minimum strip any markdown, HTML, or code blocks from feedback |
| **Database ephemeral by default** | `docker compose down -v` deletes all data | Production deployment needs a managed database (RDS, PlanetScale) external to Docker with automated backups |
| **APP_DEBUG=false not enforced** | Stack traces expose internal paths and config if accidentally set to `true` in production | Enforce via deployment pipeline; never allow `APP_DEBUG=true` on any internet-facing environment |
| **No health checks on API** | Load balancer has no endpoint to probe | Add `GET /api/health` returning `200 { "status": "ok" }` with a DB ping |
| **No structured logging** | `LOG_CHANNEL=stderr`; log entries are plain text | Use a structured JSON log format (e.g. `LOG_CHANNEL=stack` with `monolog` JSON formatter); ship to a log aggregator (Datadog, CloudWatch) |
| **No CI pipeline** | PHPStan and tests run manually | Add a GitHub Actions workflow: on PR → Pint + PHPStan + PHPUnit; on merge to main → build Docker image + push to registry |
| **CaptainHook disabled** | Pre-commit and pre-push quality gates do not run automatically | Enable hooks when `backend/` is in its own repository (see [ARCHITECTURE.md §6](ARCHITECTURE.md)) |
| **HTTPS not configured** | nginx serves HTTP only | Add TLS termination at a load balancer or reverse proxy (Cloudflare, AWS ALB) in front of nginx |

---

## 3. Scaling considerations

The POC is designed for a single student running locally. Here is what changes at each scale tier.

### Tens of concurrent students (small classroom)

- Current stateless Laravel + MySQL handles this without any changes.
- Ollama becomes the bottleneck — one request at a time on CPU. A queue (`QUEUE_CONNECTION=database` or Redis) wrapping the Ollama calls would let the API respond immediately with a job ID and poll for the result, rather than holding the HTTP connection open for 5–20 s.
- Seed a question bank so Ollama is not hit on every request.

### Hundreds of concurrent students (school-wide)

- Horizontal scaling: run multiple `php-fpm` containers behind a load balancer. Laravel is stateless (no server-side sessions used); this requires no code changes.
- MySQL read replica for the summary `GET` endpoint.
- Replace Ollama with a hosted AI API (OpenAI, Gemini) — swap the binding in `AppServiceProvider` to a new `OpenAiClient` implementing `QuestionGeneratorContract` and `FeedbackGeneratorContract`. No other code changes.
- Add Redis for caching generated questions per difficulty level.

### Thousands+ (multi-school platform)

- Introduce student accounts and multi-tenancy (school → student → session hierarchy).
- Extract question generation to an async worker process; pre-generate question pools nightly.
- Replace the simple ±1 algorithm with a per-student IRT model; persist ability estimates.
- Consider a read model (CQRS) for the summary endpoint — the current summary query joins three tables on every request.

---

## 4. Security considerations

### Current exposure

| Surface | Risk | Mitigation state |
|---|---|---|
| Sequential session IDs (`/api/practice-sessions/1`) | Enumerable — an attacker can read any session's summary | None (POC) |
| `past_questions` array from client | Server trusts client-supplied history; client could send arbitrary strings into the Ollama prompt | Bounded by `SubmitAnswerRequest` validation, but no content filtering on array items |
| Ollama endpoint (port 11434) | Exposed on the Docker host by default; any process on the host can call it | Acceptable locally; must not be published to `0.0.0.0` in any network-accessible environment |
| MySQL port (3306) | Exposed on the Docker host by default | Same as above |
| `APP_KEY` in `.env` | If committed to version control, all encrypted values are compromised | `.env` is in `.gitignore`; `.env.example` contains only safe placeholder values |

### What to add before any public exposure

1. **UUIDs for session IDs** — replace auto-increment with `uuid()` in the migration. Update `SessionDto` and all routes. This single change eliminates session enumeration.
2. **Ownership middleware** — verify the requesting student owns the session before any read or write. Requires authentication first.
3. **Prompt injection mitigation** — strip or escape user-supplied strings (answers, question text) before they are interpolated into Ollama prompts.
4. **Secrets management** — use AWS Secrets Manager, Vault, or similar instead of `.env` files for production credentials.
5. **CORS lockdown** — `CORS_ALLOWED_ORIGINS` must list only the exact production frontend origin; wildcards must never be used.

---

## 5. Why specific simplifications were made

### Synchronous Ollama calls (no queue)

The API holds the HTTP connection open while Ollama generates a response. This means the frontend waits 5–20 s per question. A job queue would eliminate this but adds Redis, a worker process, WebSocket or polling for the result — a significant infrastructure increase for a POC that runs locally. The `MathLoader` animation was added intentionally to communicate to the student that the wait is expected.

### No pre-generated question bank

Generating questions on demand from the AI guarantees variety and correct difficulty calibration. Pre-generating requires an offline pipeline, a review step (to filter bad questions), difficulty tagging, and a sampling strategy. The POC demonstrates AI-driven generation; optimisation is a post-POC concern.

### Simple ±1 adaptive algorithm

IRT requires per-student parameter estimation from historical data — it is only meaningful with a history of sessions. For a first session with a new student, ±1 is a reasonable warm-start heuristic. The algorithm is isolated in `AdaptiveAlgorithm.php` specifically so it can be replaced without touching any other file.

### No session persistence across page reload

Implementing resume-on-reload requires the frontend to store `sessionId` in `sessionStorage`, check the API on mount, and handle the case where the session has expired or been completed. This adds meaningful complexity for a POC that is always used in a single sitting.

### Frontend `useReducer` + Context instead of pure prop-drilling

Before the Context refactor, the top-level component passed 9 props down to `QuestionDisplay`. Adding three custom hooks (`useSubmitAnswer`, `useNextQuestion`, `usePracticeSession`) each of which calls `dispatch` internally required those hooks to access state without being threaded through every component. Context solves this cleanly. The trade-off is more files (reducer, context, 3 hooks) for reduced coupling. At a slightly smaller scale (pure prop-drilling, no hooks layer) the code would be equally valid — it is a judgement call at this tree depth.

### CaptainHook hooks disabled

The repository is a monorepo. Git hooks run from the repository root, not from `backend/`. CaptainHook's installation is per-working-copy. Running hooks reliably from a shared `.git` in a monorepo requires either a root-level hook dispatcher or a separate hook installation step per developer. That overhead is not justified until the backend is its own repository. The configuration is correct and ready; only the `"enabled"` flags need toggling.
