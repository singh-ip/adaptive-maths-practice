<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubmitAnswerRequest;
use App\Services\SubmitAnswerService;
use Illuminate\Http\JsonResponse;

final class AnswerController
{
    public function __construct(
        private readonly SubmitAnswerService $submitAnswerService,
    ) {}

    public function store(SubmitAnswerRequest $request, int $sessionId): JsonResponse
    {
        $dto = $this->submitAnswerService->handle(
            $sessionId,
            (int) $request->input('question_id'),
            $request->getValidatedAnswer()
        );

        return response()->json([
            'success' => true,
            'data' => $dto->toArray(),
        ], 200);
    }
}
