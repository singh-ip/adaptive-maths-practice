import { Button } from './ui/button';
import { Calculator, TrendingUp, Brain, Award } from 'lucide-react';

interface LandingPageProps {
  onStart: () => void;
  isLoading: boolean;
}

export const LandingPage = ({ onStart, isLoading }: LandingPageProps) => {
  return (
    <div className="flex flex-col items-center justify-center min-h-screen p-8">
      <div className="max-w-2xl w-full space-y-8 text-center">
        <div className="space-y-4">
          <div className="flex justify-center">
            <div className="p-4 bg-blue-100 dark:bg-blue-900 rounded-full">
              <Calculator className="w-12 h-12 text-blue-600 dark:text-blue-300" />
            </div>
          </div>
          <h1 className="text-4xl">Math Practice Session</h1>
          <p className="text-lg text-gray-600 dark:text-gray-300">
            Sharpen your multiplication skills with 5 adaptive word problems
          </p>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-3 gap-6 py-8">
          <div className="p-6 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
            <div className="flex justify-center mb-3">
              <TrendingUp className="w-8 h-8 text-green-600 dark:text-green-400" />
            </div>
            <h3 className="mb-2">Adaptive Difficulty</h3>
            <p className="text-sm text-gray-600 dark:text-gray-400">
              Questions adjust based on your performance
            </p>
          </div>

          <div className="p-6 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
            <div className="flex justify-center mb-3">
              <Brain className="w-8 h-8 text-purple-600 dark:text-purple-400" />
            </div>
            <h3 className="mb-2">AI Feedback</h3>
            <p className="text-sm text-gray-600 dark:text-gray-400">
              Get intelligent hints when you need help
            </p>
          </div>

          <div className="p-6 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
            <div className="flex justify-center mb-3">
              <Award className="w-8 h-8 text-yellow-600 dark:text-yellow-400" />
            </div>
            <h3 className="mb-2">Track Progress</h3>
            <p className="text-sm text-gray-600 dark:text-gray-400">
              See your score and review your work
            </p>
          </div>
        </div>

        <Button
          size="lg"
          onClick={onStart}
          disabled={isLoading}
          className="text-lg px-8 py-6"
        >
          {isLoading ? 'Starting...' : 'Start Practice'}
        </Button>
      </div>
    </div>
  );
};;
