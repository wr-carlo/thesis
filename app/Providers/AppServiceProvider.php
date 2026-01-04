<?php

namespace App\Providers;

use App\Models\AssessmentAttempt;
use App\Models\StudentSubject;
use App\Observers\AssessmentAttemptObserver;
use App\Observers\StudentSubjectObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        StudentSubject::observe(StudentSubjectObserver::class);
        AssessmentAttempt::observe(AssessmentAttemptObserver::class);
    }
}
