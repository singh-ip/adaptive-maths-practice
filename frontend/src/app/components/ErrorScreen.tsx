import { Button } from './ui/button';
import { UI_COPY } from '../constants/messages';

interface ErrorScreenProps {
  message: string | null;
  onRestart: () => void;
}

export const ErrorScreen = ({ message, onRestart }: ErrorScreenProps) => {
  return (
    <div className="flex items-center justify-center min-h-screen p-8">
      <div className="text-center space-y-4">
        <h2 className="text-2xl">{UI_COPY.genericErrorTitle}</h2>
        <p className="text-gray-600 dark:text-gray-400">{message}</p>
        <Button onClick={onRestart}>{UI_COPY.tryAgain}</Button>
      </div>
    </div>
  );
};
