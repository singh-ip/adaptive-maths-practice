import { createContext, useContext, useReducer, useRef, type Dispatch, type ReactNode } from 'react';
import { appReducer, initialState, type AppState, type AppAction } from './appReducer';
import { DEV_WARNINGS } from '../constants/messages';
export type { AppState, AppAction } from './appReducer';

type SessionContextValue = {
  state: AppState;
  dispatch: Dispatch<AppAction>;
  startRequest: () => AbortSignal;
  abort: () => void;
};

const SessionContext = createContext<SessionContextValue | null>(null);

export const SessionProvider = ({ children }: { children: ReactNode }) => {
  const [state, dispatch] = useReducer(appReducer, initialState);
  const abortRef = useRef<AbortController | null>(null);

  const startRequest = (): AbortSignal => {
    abortRef.current?.abort();ṅ
    const controller = new AbortController();
    abortRef.current = controller;
    return controller.signal;
  };

  const abort = () => {
    abortRef.current?.abort();
    abortRef.current = null;
  };

  return (
    <SessionContext.Provider value={{ state, dispatch, startRequest, abort }}>
      {children}
    </SessionContext.Provider>
  );
};

export const useSessionContext = (): SessionContextValue => {
  const ctx = useContext(SessionContext);
  if (!ctx) throw new Error(DEV_WARNINGS.sessionContextOutsideProvider);
  return ctx;
};
