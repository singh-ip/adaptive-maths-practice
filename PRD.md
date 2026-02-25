# Product Requirements Document
## Adaptive Maths Practice — Proof of Concept

**Version:** 1.0  
**Date:** February 2026  
**Status:** POC Complete

> **Related documents:**  
> Setup & running → [README.md](README.md)  
> System design & technical decisions → [ARCHITECTURE.md](ARCHITECTURE.md)  
> Trade-offs & production gaps → [TRADEOFFS.md](TRADEOFFS.md)

---

## 1. Overview

A single-student, browser-based maths practice tool that adapts question difficulty in real time based on answer performance. The student works through exactly five Grade 5–6 multiplication word problems per session, aided by AI-generated explanatory feedback. The system is a focused proof of concept; it is not a production platform.

---

## 2. Core User Flow (Student Perspective)

```
[Landing screen]
      │
      ▼  Click "Start Practice"
[Question screen — Q1, difficulty 5/10]
      │
      ▼  Type numeric answer → Submit
[Feedback panel animates in]
      │  Correct  → difficulty +1 for next question
      │  Wrong    → difficulty -1 for next question
      ▼  Click "Next Question"
[Question screen — Q2 … Q5, difficulty adjusts each round]
      │
      ▼  After Q5 is answered → "See Results"
[Summary screen]
      │  Shows score, difficulty progression chart,
      │  per-question breakdown with AI explanation
      │
      ▼  Click "Start Again"   (available at any point)
[Landing screen — fresh session]
```

**"Start Again" is intentionally available at every stage.** A student may abandon a session mid-way and restart without penalty. The button is disabled during any in-flight network request to prevent duplicate submissions, and clicking it aborts any outstanding fetch before resetting state.

**Difficulty display on the question screen** uses three human-readable bands derived from the 1–10 internal scale:

| Internal scale | Label shown | Colour |
|---|---|---|
| 1–4 | Easy | Green |
| 5–7 | Medium | Amber |
| 8–10 | Hard | Red |

Labels and thresholds are defined once in `DIFFICULTY_THRESHOLDS` inside `QuestionDisplay.tsx` and computed by pure module-level functions (`getDifficultyColor`, `getDifficultyLabel`), never duplicated.

---

## 3. Functional Requirements

### 3.1 Session lifecycle
| # | Requirement |
|---|---|
| F1 | A session starts when the student clicks "Start Practice"; the backend creates a session record and returns the first question. |
| F2 | Each session contains exactly **5** questions (`AdaptiveAlgorithm::TOTAL_QUESTIONS`). The constant is the single source of truth on both backend and frontend. |
| F3 | Starting difficulty is **5/10** (`AdaptiveAlgorithm::START_DIFFICULTY`). |
| F4 | After each answer the algorithm moves difficulty ±1 (capped 1–10). |
| F5 | Once 5 answers are recorded the session is permanently marked `completed` and no further answers are accepted. |
| F6 | The summary screen displays: total score, score %, difficulty progression, and per-question AI feedback. |

### 3.2 Adaptive algorithm
- Correct answer → `min(current + 1, 10)`
- Wrong answer → `max(current - 1, 1)`
- Logic lives exclusively in `AdaptiveAlgorithm.php`; no client-side difficulty calculation.

### 3.3 AI feedback
- Every submitted answer triggers an AI explanation (if incorrect), generated via Ollama running `llama3.2`.
- Feedback is a concise, encouraging plain-text explanation suitable for a Grade 5–6 student.
- If the AI call fails, a deterministic fallback string is returned so the session never stalls.

### 3.4 Previous-question skip logic
The backend passes the list of question texts already used in the current session (`past_questions[]`) when asking the AI to generate the next question. The `QuestionGeneratorContract` interface explicitly declares this parameter:

```php
public function generate(int $difficulty, array $previousQuestions = []): array;
```

This prevents the AI from repeating a question that was already shown in the same session. The frontend accumulates `pastQuestions: string[]` in `AppState` and forwards the array on every `submitAnswer` call.

---

## 4. API Contract

All endpoints are prefixed with `/api`. Responses follow `{ "success": bool, "data": {...} }`.

### POST `/api/practice-sessions`
Start a new session.

**Request body:** _(none)_

**Response `201`:**
```json
{
  "success": true,
  "data": {
    "session_id": 1,
    "question_id": 42,
    "question_number": 1,
    "question": "A farmer has 12 rows of 8 apple trees. How many trees are there in total?",
    "difficulty": 5
  }
}
```

---

### POST `/api/practice-sessions/{sessionId}/answers`
Submit an answer and receive feedback + next question (or session-complete signal).

**Request body:**
```json
{
  "question_id": 42,
  "answer": 96,
  "past_questions": [
    "A farmer has 12 rows of 8 apple trees. How many trees are there in total?"
  ]
}
```

`past_questions` is an array of question texts already shown in this session. The AI generator uses these to avoid producing a duplicate question.

**Response `200` (session continues):**
```json
{
  "success": true,
  "data": {
    "session_complete": false,
    "answer_correct": true,
    "correct_answer": 96,
    "your_answer": 96,
    "feedback": "Great work! 12 × 8 = 96. Multiplying rows by columns gives the total.",
    "progress": { "answered": 1, "total": 5 },
    "next_question_id": 43,
    "next_question_number": 2,
    "next_question": "There are 9 bags with 7 oranges each. How many oranges in total?",
    "next_difficulty": 6
  }
}
```

**Response `200` (session complete — `next_*` fields are `null`):**
```json
{
  "success": true,
  "data": {
    "session_complete": true,
    "answer_correct": false,
    "correct_answer": 63,
    "your_answer": 54,
    "feedback": "Not quite — 9 × 7 = 63, not 54. Try skip-counting by 9.",
    "progress": { "answered": 5, "total": 5 },
    "next_question_id": null,
    "next_question_number": null,
    "next_question": null,
    "next_difficulty": null
  }
}
```

---

### GET `/api/practice-sessions/{sessionId}`
Retrieve the full session summary.

**Response `200`:**
```json
{
  "success": true,
  "data": {
    "session_id": 1,
    "status": "completed",
    "total_questions": 5,
    "correct_answers": 3,
    "score_percentage": 60.0,
    "difficulty_progression": [5, 6, 7, 6, 5],
    "details": [
      {
        "question_number": 1,
        "question": "A farmer has 12 rows...",
        "correct_answer": 96,
        "your_answer": 96,
        "correct": true,
        "difficulty": 5,
        "feedback": "Great work! 12 × 8 = 96."
      }
      // … 4 more
    ]
  }
}
```

---

## 5. Out of Scope (deliberate POC exclusions)

| Feature | Reason excluded |
|---|---|
| Authentication / student accounts | Single anonymous student per session; no identity required for POC |
| Multiple concurrent students | No session isolation or auth needed for single-user demo |
| Persistent student history across sessions | Out of product scope; each session is self-contained |
| Subjects beyond multiplication | Scope-locked to Grade 5–6 multiplication word problems |
| Mobile-responsive layout | Desktop browser sufficient for POC evaluation |
| Question difficulty tiers below Easy or above Hard | 1–10 internal scale covers Grade 5–6 range adequately |
| Leaderboard / gamification | Post-POC feature |
| CI/CD pipeline | CaptainHook hooks are configured but disabled; see [ARCHITECTURE.md](ARCHITECTURE.md) |
| Internationalisation | `lang/en/` exists for structure; only English supported |
| Rate limiting / abuse prevention | POC runs locally; not internet-facing |

---

## 6. Key Assumptions & Constraints

| # | Assumption / Constraint |
|---|---|
| A1 | Ollama runs locally inside Docker; no external AI API cost or latency applies. |
| A2 | The `llama3.2` model must be fully pulled before demo (see [README.md](README.md) — Ollama setup). |
| A3 | MySQL data is ephemeral per Docker volume; no migration run = empty DB on fresh `docker compose up`. |
| A4 | `php artisan migrate` must be run inside the container on first start: `docker compose exec php php artisan migrate`. |
| A5 | Session IDs are integer auto-increments from MySQL; they are treated as opaque strings in the frontend to decouple the representation. |
| A6 | AI feedback may take 10–30 seconds per question depending on host hardware; the loading animation (Framer Motion `MathLoader`) is intentional, not a bug. |
| A7 | The adaptive algorithm is deliberately simple (±1 per answer). This is a POC; a more sophisticated IRT model would replace `AdaptiveAlgorithm.php` without touching any other layer. |
| A8 | All five questions are multiplication word problems. The AI generator is prompted specifically for Grade 5–6 level; the difficulty parameter controls numerical complexity, not subject domain. |
| A9 | CaptainHook git hooks are disabled while the project lives in a monorepo. They are ready to enable the moment `backend/` is extracted to its own repository. |
| A10 | PHPStan is run manually (`docker compose exec php composer check:stan`) rather than in CI. Level 6 enforces type safety sufficient for a POC codebase. |
