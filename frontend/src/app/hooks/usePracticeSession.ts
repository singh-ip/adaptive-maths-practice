import { useSessionContext } from '../context/SessionContext';
import { apiClient } from '../services/service';
import { toast } from 'sonner';
import { ERROR_MESSAGES } from '../constants/messages';
import { isAbortError, extractErrorMessage } from '../utils/error';

export const usePracticeSession = () => {
  const { state, dispatch, startRequest, abort } = useSessionContext();

  const handleStartSession = async () => {
    const signal = startRequest();
    dispatch({ type: 'START_SESSION_REQUEST' });
    try {
      const result = await apiClient.startSession(signal);
      dispatch({
        type: 'START_SESSION_SUCCESS',
        sessionId: result.sessionId,
        question: result.firstQuestion,
      });
    } catch (error) {
      if (isAbortError(error)) return;
      const errorMessage = extractErrorMessage(error, ERROR_MESSAGES.startSession);
      dispatch({ type: 'START_SESSION_ERROR', error: errorMessage });
      toast.error(errorMessage);
    }
  };

  const handleRestart = () => {
    // Cancel any in-flight request before resetting state so a slow
    // response cannot dispatch into the freshly-restarted session.
    abort();
    dispatch({ type: 'RESTART' });
  };

  return { state, handleStartSession, handleRestart };
};

export type { AppState } from '../context/SessionContext';
