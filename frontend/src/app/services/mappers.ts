import type { RawAnswerResponse, Question, Feedback } from '../types/api';
import { FEEDBACK_MESSAGES } from '../constants/messages';

export const mapRawQuestion = (
  id: number,
  text: string,
  difficulty: number,
  questionNumber: number,
): Question => {
  return { id, questionNumber, text, difficulty };
};

export const buildFeedback = (raw: RawAnswerResponse): Feedback => {
  if (raw.answer_correct) {
    return { isCorrect: true, message: FEEDBACK_MESSAGES.correct };
  }

  return {
    isCorrect: false,
    message: FEEDBACK_MESSAGES.incorrect,
    explanation: raw.feedback ?? undefined,
    correctAnswer: raw.correct_answer,
  };
};
