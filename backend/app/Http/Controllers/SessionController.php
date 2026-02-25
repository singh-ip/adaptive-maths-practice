<?php

namespace App\Http\Controllers;

use App\Http\Requests\GetSessionRequest;
use App\Services\SessionService;
use App\Services\SessionSummaryService;
use Illuminate\Http\JsonResponse;

final class SessionController
{
    public function __construct(
        private readonly SessionService $sessionService,
        private readonly SessionSummaryService $sessionSummaryService,
    ) {}

    public function store(): JsonResponse
    {
        $dto = $this->sessionService->create();

        return response()->json([
            'success' => true,
            'data' => $dto->toArray(),
        ], 201);
    }

    public function show(GetSessionRequest $request, int $sessionId): JsonResponse
    {
        $dto = $this->sessionSummaryService->build($sessionId);

        return response()->json([
            'success' => true,
            'data' => $dto->toArray(),
        ], 200);
    }
}
