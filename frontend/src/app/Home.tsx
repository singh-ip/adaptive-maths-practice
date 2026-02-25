import { ThemeToggle } from './components/ThemeToggle';
import { LandingPage } from './components/LandingPage';
import { QuestionDisplay } from './components/QuestionDisplay';
import { CompletionScreen } from './components/CompletionScreen';
import { LoadingQuestion } from './components/LoadingQuestion';
import { ErrorScreen } from './components/ErrorScreen';
import { Toaster } from 'sonner';
import { usePracticeSession } from './hooks/usePracticeSession';

const TOTAL_QUESTIONS = 5;

export const Home = () => {
  const { state, handleStartSession, handleRestart } = usePracticeSession();

  return (
    <div className="min-h-screen bg-gray-50 dark:bg-gray-900">
      <div className="fixed top-4 right-4 z-50">
        <ThemeToggle />
      </div>

      {state.status === 'landing' && (
        <LandingPage onStart={handleStartSession} isLoading={state.isLoading} />
      )}

      {state.status === 'in-progress' && !state.currentQuestion && (
        <LoadingQuestion />
      )}

      {state.status === 'in-progress' && state.currentQuestion && (
        <QuestionDisplay
          question={state.currentQuestion}
          onRestart={handleRestart}
          totalQuestions={TOTAL_QUESTIONS}
        />
      )}

      {state.status === 'completed' && state.summary && (
        <CompletionScreen summary={state.summary} onRestart={handleRestart} />
      )}

      {state.status === 'error' && (
        <ErrorScreen message={state.error} onRestart={handleRestart} />
      )}

      <Toaster position="top-center" />
    </div>
  );
};
