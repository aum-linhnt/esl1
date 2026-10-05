<?php

namespace App\Providers;

use App\Events\MessageSent;
use App\Listeners\SendNewMessageNotification;
use App\Models\AssignmentSubmission;
use App\Models\Course;
use App\Models\Conversation;
use App\Policies\AssignmentSubmissionPolicy;
use App\Policies\CoursePolicy;
use App\Policies\MessagePolicy;
use App\Services\ActionLogService;
use App\Services\MessagingService;
use App\Services\NotificationService;
use App\Services\SettingService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(SettingService::class);
        $this->app->singleton(MessagingService::class);
        $this->app->singleton(NotificationService::class);
        $this->app->singleton(ActionLogService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Overlay admin-edited settings (DB) onto config. Must never break boot:
        // on a fresh install the settings/cache tables may not exist yet.
        try {
            $this->app->make(SettingService::class)->applyToConfig();
        } catch (Throwable) {
            // Fall back to .env/config defaults.
        }

        // Register Policies
        Gate::policy(Course::class, CoursePolicy::class);
        Gate::policy(Conversation::class, MessagePolicy::class);
        Gate::policy(AssignmentSubmission::class, AssignmentSubmissionPolicy::class);

        // Register Event Listeners
        Event::listen(MessageSent::class, SendNewMessageNotification::class);
    }
}
