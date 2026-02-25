import type { Question, Feedback, SessionSummary } from '../types/api';

export type AppState = {
  status: 'landing' | 'in-progress' | 'completed' | 'error';
  sessionId: string | null;
  currentQuestion: Question | null;
  nextQuestion: Question | null;
  feedback: Feedback | null;
  summary: SessionSummary | null;
  isSessionComplete: boolean;
  isLoading: boolean;
  error: string | null;
  /** True when the last answer submission failed — signals QuestionDisplay to reset its local form state */
  submissionFailed: boolean;
};

export type AppAction =
  | { type: 'START_SESSION_REQUEST' }
  | { type: 'START_SESSION_SUCCESS'; sessionId: string; question: Question }
  | { type: 'START_SESSION_ERROR'; error: string }
  | { type: 'SUBMIT_ANSWER_REQUEST' }
  | { type: 'SUBMIT_ANSWER_SUCCESS'; feedback: Feedback; nextQuestion: Question | null; isComplete: boolean }
  | { type: 'SUBMIT_ANSWER_ERROR'; error: string }
  | { type: 'LOAD_NEXT_QUESTION' }
  | { type: 'LOAD_SUMMARY_REQUEST' }
  | { type: 'LOAD_SUMMARY_SUCCESS'; summary: SessionSummary }
  | { type: 'LOAD_SUMMARY_ERROR'; error: string }
  | { type: 'RESTART' };

export const initialState: AppState = {
  status: 'landing',
  sessionId: null,
  currentQuestion: null,
  nextQuestion: null,
  feedback: null,
  summary: null,
  isSessionComplete: false,
  isLoading: false,
  error: null,
  submissionFailed: false,
};

export const appReducer = (state: AppState, action: AppAction): AppState => {
  switch (action.type) {
    case 'START_SESSION_REQUEST':
      return { ...state, isLoading: true, error: null };

    case 'START_SESSION_SUCCESS':
      return {
        ...state,
        status: 'in-progress',
        sessionId: action.sessionId,
        currentQuestion: action.question,
        isLoading: false,
        feedback: null,
      };

    case 'START_SESSION_ERROR':
      return { ...state, status: 'error', error: action.error, isLoading: false };

    case 'SUBMIT_ANSWER_REQUEST':
      return { ...state, isLoading: true, error: null, submissionFailed: false };

    case 'SUBMIT_ANSWER_SUCCESS':
      return {
        ...state,
        feedback: action.feedback,
        nextQuestion: action.nextQuestion,
        isSessionComplete: action.isComplete,
        isLoading: false,
        submissionFailed: false,
      };

    case 'SUBMIT_ANSWER_ERROR':
      return { ...state, error: action.error, isLoading: false, submissionFailed: true };

    case 'LOAD_NEXT_QUESTION':
      return {
        ...state,
        currentQuestion: state.nextQuestion,
        nextQuestion: null,
        feedback: null,
        isLoading: false,
      };

    case 'LOAD_SUMMARY_REQUEST':
      return { ...state, isLoading: true, error: null };

    case 'LOAD_SUMMARY_SUCCESS':
      return {
        ...state,
        status: 'completed',
        summary: action.summary,
        isLoading: false,
      };

    case 'LOAD_SUMMARY_ERROR':
      return { ...state, status: 'error', error: action.error, isLoading: false };

    case 'RESTART':
      return initialState;

    default: {
      void (action satisfies never);
      return state;
    }
  }
};
