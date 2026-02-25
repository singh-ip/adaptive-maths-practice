import { useState, useEffect, type ReactNode, type FormEvent } from 'react';
import { Button } from './ui/button';
import { Input } from './ui/input';
import { Label } from './ui/label';
import { Card, CardContent, CardHeader, CardTitle } from './ui/card';
import { Progress } from './ui/progress';
import { Loader2, AlertCircle, Lightbulb, RotateCcw } from 'lucide-react';
import { MathLoader } from './MathLoader';
import { useSessionContext } from '../context/SessionContext';
import { useSubmitAnswer } from '../hooks/useSubmitAnswer';
import { useNextQuestion } from '../hooks/useNextQuestion';
import type { Question } from '../types/api';

const DIFFICULTY_THRESHOLDS = { easy: 3, medium: 7 } as const;

const getDifficultyColor = (difficulty: number): string => {
  if (difficulty <= DIFFICULTY_THRESHOLDS.easy) return 'text-green-600 dark:text-green-400';
  if (difficulty <= DIFFICULTY_THRESHOLDS.medium) return 'text-yellow-600 dark:text-yellow-400';
  return 'text-red-600 dark:text-red-400';
};

const getDifficultyLabel = (difficulty: number): string => {
  if (difficulty <= DIFFICULTY_THRESHOLDS.easy) return 'Easy';
  if (difficulty <= DIFFICULTY_THRESHOLDS.medium) return 'Medium';
  return 'Hard';
};

const FeedbackBox = ({
  icon,
  children,
  className,
}: {
  icon: ReactNode;
  children: ReactNode;
  className: string;
}) => {
  return (
    <div
      role="alert"
      className={`flex items-start gap-3 w-full rounded-lg border px-4 py-3 text-sm ${className}`}
    >
      <span className="mt-0.5 shrink-0">{icon}</span>
      <div>{children}</div>
    </div>
  );
};

interface QuestionDisplayProps {
  question: Question;
  onRestart: () => void;
  totalQuestions: number;
}

export const QuestionDisplay = ({ question, onRestart, totalQuestions }: QuestionDisplayProps) => {
  const { state: { feedback, isLoading: isSubmitting, submissionFailed, isSessionComplete } } =
    useSessionContext();
  const submitAnswer = useSubmitAnswer();
  const nextQuestion = useNextQuestion();

  const [answer, setAnswer] = useState('');
  const [hasSubmitted, setHasSubmitted] = useState(false);

  useEffect(() => {
    setAnswer('');
    setHasSubmitted(false);
  }, [question.id]);

  useEffect(() => {
    if (submissionFailed) {
      setAnswer('');
      setHasSubmitted(false);
    }
  }, [submissionFailed]);

  const handleSubmit = (e: FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    if (!answer || hasSubmitted) return;

    const numAnswer = parseInt(answer, 10);
    if (isNaN(numAnswer)) return;

    setHasSubmitted(true);
    void submitAnswer(numAnswer);
  };

  const progress = (question.questionNumber / totalQuestions) * 100;

  return (
    <div className="flex flex-col items-center justify-center min-h-screen p-8">
      <div className="max-w-2xl w-full space-y-6">
        <div className="space-y-2">
          <div className="flex justify-between items-center">
            <span className="text-sm text-gray-600 dark:text-gray-400">
              Question {question.questionNumber} of {totalQuestions}
            </span>
            <span className={`text-sm capitalize ${getDifficultyColor(question.difficulty)}`}>
              {getDifficultyLabel(question.difficulty)}
            </span>
          </div>
          <Progress value={progress} className="h-2" />
        </div>

        <Card>
          <CardHeader>
            <CardTitle>Problem {question.questionNumber}</CardTitle>
          </CardHeader>
          <CardContent className="space-y-6">
            <p className="text-lg leading-relaxed">{question.text}</p>

            {!hasSubmitted ? (
              <form onSubmit={handleSubmit} className="space-y-4">
                <div className="space-y-2">
                  <Label htmlFor="answer">Your Answer</Label>
                  <Input
                    id="answer"
                    type="number"
                    min="0"
                    value={answer}
                    onChange={(e) => setAnswer(e.target.value)}
                    placeholder="Enter your answer"
                    className="text-lg"
                    autoFocus
                    disabled={isSubmitting}
                  />
                </div>
                <Button
                  type="submit"
                  className="w-full"
                  disabled={!answer || isSubmitting}
                >
                  {isSubmitting ? (
                    <>
                      <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                      Checking...
                    </>
                  ) : (
                    'Submit Answer'
                  )}
                </Button>
              </form>
            ) : isSubmitting ? (
              <MathLoader message="Analyzing your answer..." />
            ) : feedback ? (
              <div className="space-y-4" aria-live="polite" aria-atomic="true">
                {feedback.isCorrect ? (
                  // Correct answer — visually separate from the input area
                  <div className="flex flex-col items-center gap-3 py-6 text-center">
                    <div className="text-6xl">🎉</div>
                    <p className="text-2xl font-bold text-green-600 dark:text-green-400">
                      {feedback.message}
                    </p>
                  </div>
                ) : (
                  <div className="space-y-3">
                    <FeedbackBox
                      icon={<AlertCircle className="h-4 w-4 text-red-600 dark:text-red-400" />}
                      className="border-red-200 bg-red-50 dark:bg-red-900/20 dark:border-red-800"
                    >
                      <span className="text-red-800 dark:text-red-200">{feedback.message}</span>
                    </FeedbackBox>

                    {feedback.explanation && (
                      <FeedbackBox
                        icon={<Lightbulb className="h-4 w-4 text-blue-600 dark:text-blue-400" />}
                        className="border-blue-200 bg-blue-50 dark:bg-blue-900/20 dark:border-blue-800"
                      >
                        <p className="text-blue-800 dark:text-blue-200">{feedback.explanation}</p>
                      </FeedbackBox>
                    )}

                    {feedback.correctAnswer !== undefined && (
                      <div className="p-4 bg-gray-100 dark:bg-gray-800 rounded-lg">
                        <p className="text-sm text-gray-600 dark:text-gray-400 mb-1">
                          Correct Answer:
                        </p>
                        <p className="text-2xl font-bold">{feedback.correctAnswer}</p>
                      </div>
                    )}
                  </div>
                )}

                <Button onClick={() => void nextQuestion()} className="w-full" disabled={isSubmitting}>
                  {isSubmitting ? (
                    <>
                      <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                      Loading...
                    </>
                  ) : isSessionComplete ? (
                    'See Results'
                  ) : (
                    'Next Question →'
                  )}
                </Button>

                <button
                  type="button"
                  onClick={onRestart}
                  disabled={isSubmitting}
                  className="flex items-center justify-center gap-1.5 w-full text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 disabled:opacity-40 disabled:pointer-events-none cursor-pointer transition-colors py-1"
                >
                  <RotateCcw className="h-3.5 w-3.5" />
                  Start Again
                </button>
              </div>
            ) : null}
          </CardContent>
        </Card>
      </div>
    </div>
  );
};