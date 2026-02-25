<?php

use App\Http\Controllers\AnswerController;
use App\Http\Controllers\SessionController;
use Illuminate\Support\Facades\Route;

Route::post('/practice-sessions', [SessionController::class, 'store']);
Route::get('/practice-sessions/{sessionId}', [SessionController::class, 'show']);
Route::post('/practice-sessions/{sessionId}/answers', [AnswerController::class, 'store']);
