import { Button } from './ui/button';
import { Card, CardContent, CardHeader, CardTitle } from './ui/card';
import { Trophy, CheckCircle, XCircle, RotateCcw } from 'lucide-react';
import type { SessionSummary } from '../types/api';

interface CompletionScreenProps {
  summary: SessionSummary;
  onRestart: () => void;
}

export const CompletionScreen = ({ summary, onRestart }: CompletionScreenProps) => {
  const getScoreColor = (score: number) => {
    if (score >= 80) return 'text-green-600 dark:text-green-400';
    if (score >= 60) return 'text-yellow-600 dark:text-yellow-400';
    return 'text-red-600 dark:text-red-400';
  };

  const getScoreMessage = (score: number) => {
    if (score === 100) return 'Perfect Score! Outstanding! 🌟';
    if (score >= 80) return 'Excellent Work! 🎉';
    if (score >= 60) return 'Good Job! Keep Practicing! 👍';
    return 'Keep Going! Every Mistake is a Learning Opportunity! 💪';
  };

  return (
    <div className="flex flex-col items-center justify-center min-h-screen p-8">
      <div className="max-w-3xl w-full space-y-6">
        <div className="text-center space-y-4">
          <div className="flex justify-center">
            <div className="p-6 bg-yellow-100 dark:bg-yellow-900 rounded-full">
              <Trophy className="w-16 h-16 text-yellow-600 dark:text-yellow-300" />
            </div>
          </div>
          <h1 className="text-4xl">Practice Complete!</h1>
          <p className="text-lg text-gray-600 dark:text-gray-300">
            {getScoreMessage(summary.score)}
          </p>
        </div>

        <Card>
          <CardHeader>
            <CardTitle>Your Results</CardTitle>
          </CardHeader>
          <CardContent className="space-y-6">
            <div className="grid grid-cols-3 gap-4">
              <div className="text-center p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                <div className={`text-3xl mb-2 ${getScoreColor(summary.score)}`}>
                  {summary.score}%
                </div>
                <div className="text-sm text-gray-600 dark:text-gray-400">Score</div>
              </div>

              <div className="text-center p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                <div className="text-3xl text-green-600 dark:text-green-400 mb-2 flex items-center justify-center gap-1">
                  <CheckCircle className="w-6 h-6" />
                  {summary.correctAnswers}
                </div>
                <div className="text-sm text-gray-600 dark:text-gray-400">Correct</div>
              </div>

              <div className="text-center p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                <div className="text-3xl text-red-600 dark:text-red-400 mb-2 flex items-center justify-center gap-1">
                  <XCircle className="w-6 h-6" />
                  {summary.totalQuestions - summary.correctAnswers}
                </div>
                <div className="text-sm text-gray-600 dark:text-gray-400">Incorrect</div>
              </div>
            </div>

            <div className="space-y-3">
              <h3 className="font-semibold">Question Review</h3>
              <div className="space-y-2">
                {summary.attempts.map((attempt) => (
                  <div
                    key={attempt.questionNumber}
                    className={`p-4 rounded-lg border ${
                      attempt.isCorrect
                        ? 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800'
                        : 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800'
                    }`}
                  >
                    <div className="flex items-start gap-3">
                      {attempt.isCorrect ? (
                        <CheckCircle className="w-5 h-5 text-green-600 dark:text-green-400 mt-0.5 flex-shrink-0" />
                      ) : (
                        <XCircle className="w-5 h-5 text-red-600 dark:text-red-400 mt-0.5 flex-shrink-0" />
                      )}
                      <div className="flex-1 space-y-2">
                        <p className="text-sm">
                          <span className="font-medium">Q{attempt.questionNumber}:</span> {attempt.questionText}
                        </p>
                        <div className="flex gap-4 text-sm">
                          <span>
                            Your answer: <span className="font-medium">{attempt.userAnswer}</span>
                          </span>
                          {!attempt.isCorrect && (
                            <span>
                              Correct answer:{' '}
                              <span className="font-medium">{attempt.correctAnswer}</span>
                            </span>
                          )}
                        </div>
                        {attempt.feedbackExplanation && (
                          <p className="text-sm text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 p-2 rounded">
                            💡 {attempt.feedbackExplanation}
                          </p>
                        )}
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            </div>

            <Button onClick={onRestart} className="w-full" size="lg">
              <RotateCcw className="mr-2 h-5 w-5" />
              Start New Practice Session
            </Button>
          </CardContent>
        </Card>
      </div>
    </div>
  );
}
