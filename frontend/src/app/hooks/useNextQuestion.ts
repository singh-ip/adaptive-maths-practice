import { useSessionContext } from '../context/SessionContext';
import { apiClient } from '../services/service';
import { toast } from 'sonner';
import { ERROR_MESSAGES, DEV_WARNINGS } from '../constants/messages';
import { isAbortError, extractErrorMessage } from '../utils/error';

export const useNextQuestion = () => {
  const { state, dispatch, startRequest } = useSessionContext();

  return async () => {
    if (!state.isSessionComplete) {
      dispatch({ type: 'LOAD_NEXT_QUESTION' });
      return;
    }

    if (!state.sessionId) {
      console.error(DEV_WARNINGS.nextQuestionMissingSessionId);
      return;
    }

    const signal = startRequest();
    dispatch({ type: 'LOAD_SUMMARY_REQUEST' });
    try {
      const summary = await apiClient.getSessionSummary(state.sessionId, signal);
      dispatch({ type: 'LOAD_SUMMARY_SUCCESS', summary });
    } catch (error) {
      if (isAbortError(error)) return;
      const errorMessage = extractErrorMessage(error, ERROR_MESSAGES.loadSummary);
      dispatch({ type: 'LOAD_SUMMARY_ERROR', error: errorMessage });
      toast.error(errorMessage);
    }
  };
};
