import { Card, CardContent, CardHeader } from './ui/card';
import { Skeleton } from './ui/skeleton';
import { MathLoader } from './MathLoader';

export const LoadingQuestion = () => {
  return (
    <div className="flex flex-col items-center justify-center min-h-screen p-8">
      <div className="max-w-2xl w-full space-y-6">
        <div className="space-y-2">
          <Skeleton className="h-4 w-40" />
          <Skeleton className="h-2 w-full" />
        </div>

        <Card>
          <CardHeader>
            <Skeleton className="h-6 w-32" />
          </CardHeader>
          <CardContent className="space-y-6">
            <MathLoader message="Preparing your question..." />
          </CardContent>
        </Card>
      </div>
    </div>
  );
};