import type {
  RawSessionStart,
  RawAnswerResponse,
  RawSessionSummary,
  SessionStart,
  AnswerSubmission,
  AnswerResponse,
  SessionSummary,
  QuestionAttempt,
} from '../types/api';

import { post, get } from './apiClient';
import { mapRawQuestion, buildFeedback } from './mappers';

const SESSIONS_BASE = '/api/practice-sessions';

export const apiClient = {
  async startSession(signal?: AbortSignal): Promise<SessionStart> {
    const data = await post<RawSessionStart>(SESSIONS_BASE, undefined, signal);

    return {
      sessionId: data.session_id,
      firstQuestion: mapRawQuestion(data.question_id, data.question, data.difficulty, data.question_number),
    };
  },

  async submitAnswer(
    sessionId: string,
    submission: AnswerSubmission,
    signal?: AbortSignal,
  ): Promise<AnswerResponse> {
    const data = await post<RawAnswerResponse>(`${SESSIONS_BASE}/${sessionId}/answers`, {
      question_id: submission.questionId,
      answer: submission.answer,
      past_questions: submission.pastQuestions,
    }, signal);

    const feedback = buildFeedback(data);

    let nextQuestion = undefined;
    if (
      !data.session_complete &&
      data.next_question_id !== null &&
      data.next_question !== null &&
      data.next_difficulty !== null &&
      data.next_question_number !== null
    ) {
      nextQuestion = mapRawQuestion(
        data.next_question_id,
        data.next_question,
        data.next_difficulty,
        data.next_question_number,
      );
    }

    return {
      feedback,
      nextQuestion,
      sessionComplete: data.session_complete,
    };
  },

  async getSessionSummary(sessionId: string, signal?: AbortSignal): Promise<SessionSummary> {
    const data = await get<RawSessionSummary>(`${SESSIONS_BASE}/${sessionId}`, signal);

    const attempts: QuestionAttempt[] = data.details.map((detail) => ({
      questionNumber: detail.question_number,
      questionText: detail.question,
      difficulty: detail.difficulty,
      userAnswer: detail.your_answer ?? 0,
      correctAnswer: detail.correct_answer,
      isCorrect: detail.correct,
      feedbackExplanation: detail.correct ? undefined : (detail.feedback ?? undefined),
    }));

    return {
      sessionId: data.session_id,
      status: data.status as SessionSummary['status'],
      totalQuestions: data.total_questions,
      correctAnswers: data.correct_answers,
      score: Math.round(data.score_percentage),
      difficultyProgression: data.difficulty_progression,
      attempts,
    };
  },
};
