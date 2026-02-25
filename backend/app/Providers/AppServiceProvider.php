<?php

namespace App\Providers;

use App\Contracts\FeedbackGeneratorContract;
use App\Contracts\QuestionGeneratorContract;
use App\Services\FeedbackGeneratorService;
use App\Services\QuestionGeneratorService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            QuestionGeneratorContract::class,
            QuestionGeneratorService::class
        );

        $this->app->bind(
            FeedbackGeneratorContract::class,
            FeedbackGeneratorService::class
        );
    }

    public function boot(): void
    {
        //
    }
}
