/**
 * User-facing copy used across the application.
 */

export const FEEDBACK_MESSAGES = {
  correct: 'Correct! Well done! 🎉',
  incorrect: "Not quite right, but that's okay! Learning from mistakes helps us grow.",
} as const;

export const ERROR_MESSAGES = {
  startSession: 'Failed to start session',
  submitAnswer: 'Failed to submit answer',
  loadSummary: 'Failed to load summary',
  apiError: 'API returned success: false',
  missingConfig: 'Service is temporarily unavailable. Please contact support.',
} as const;

export const UI_COPY = {
  genericErrorTitle: 'Oops! Something went wrong',
  tryAgain: 'Try Again',
} as const;
