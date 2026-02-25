export interface Question {
  id: number;
  questionNumber: number;
  text: string;
  difficulty: number;
}

export interface Feedback {
  isCorrect: boolean;
  message: string;
  explanation?: string;
  correctAnswer?: number;
}

export interface AnswerSubmission {
  questionId: number;
  answer: number;
  pastQuestions: string[];
}

export interface AnswerResponse {
  feedback: Feedback;
  nextQuestion?: Question;
  sessionComplete: boolean;
}

export interface SessionStart {
  sessionId: string;
  firstQuestion: Question;
}

export interface QuestionAttempt {
  questionNumber: number;
  questionText: string;
  difficulty: number;
  userAnswer: number;
  correctAnswer: number;
  isCorrect: boolean;
  /** AI explanation returned by the backend — only present for incorrect answers */
  feedbackExplanation?: string;
}

export type SessionStatus = 'not-started' | 'in-progress' | 'completed';

export interface SessionSummary {
  sessionId: string;
  status: SessionStatus;
  totalQuestions: number;
  correctAnswers: number;
  score: number; // percentage
  difficultyProgression: number[];
  attempts: QuestionAttempt[];
}

export interface ApiEnvelope<T> {
  success: boolean;
  data: T;
}

/** POST /api/practice-sessions */
export interface RawSessionStart {
  session_id: string;
  question_id: number;
  question_number: number;
  question: string;
  difficulty: number;
}

/** POST /api/practice-sessions/{id}/answers */
export interface RawAnswerResponse {
  session_complete: boolean;
  answer_correct: boolean;
  correct_answer: number;
  your_answer: number;
  feedback: string | null;
  progress: {
    current_question: number;
    total_questions: number;
    correct_so_far: number;
  } | null;
  next_question_id: number | null;
  next_question_number: number | null;
  next_question: string | null;
  next_difficulty: number | null;
}

/** One entry in the `details` array of {@link RawSessionSummary} */
export interface RawQuestionDetail {
  question_number: number;
  question: string;
  correct_answer: number;
  your_answer: number | null;
  correct: boolean;
  difficulty: number;
  feedback: string | null;
}

/** GET /api/practice-sessions/{id} */
export interface RawSessionSummary {
  session_id: string;
  status: string;
  total_questions: number;
  correct_answers: number;
  score_percentage: number;
  difficulty_progression: number[];
  details: RawQuestionDetail[];
}
