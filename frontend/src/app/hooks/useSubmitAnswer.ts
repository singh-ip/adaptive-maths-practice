import { useSessionContext } from '../context/SessionContext';
import { apiClient } from '../services/service';
import { toast } from 'sonner';
import { ERROR_MESSAGES, DEV_WARNINGS } from '../constants/messages';
import { isAbortError, extractErrorMessage } from '../utils/error';

export const useSubmitAnswer = () => {
  const { state, dispatch, startRequest } = useSessionContext();

  return async (answer: number) => {
    if (!state.sessionId || !state.currentQuestion) {
      console.error(DEV_WARNINGS.submitAnswerMissingState);
      return;
    }

    const signal = startRequest();
    dispatch({ type: 'SUBMIT_ANSWER_REQUEST' });
    try {
      const result = await apiClient.submitAnswer(
        state.sessionId,
        { questionId: state.currentQuestion.id, answer },
        signal,
      );
      dispatch({
        type: 'SUBMIT_ANSWER_SUCCESS',
        feedback: result.feedback,
        nextQuestion: result.nextQuestion ?? null,
        isComplete: result.sessionComplete,
      });
    } catch (error) {
      if (isAbortError(error)) return;
      const errorMessage = extractErrorMessage(error, ERROR_MESSAGES.submitAnswer);
      dispatch({ type: 'SUBMIT_ANSWER_ERROR', error: errorMessage });
      toast.error(errorMessage);
    }
  };
};
