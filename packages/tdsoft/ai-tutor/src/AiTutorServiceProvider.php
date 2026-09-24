<?php

namespace TDSoft\AiTutor;

use Illuminate\Support\ServiceProvider;
use TDSoft\AiTutor\Contracts\{ActorResolver, Entitlements, LmsContextAdapter};
use TDSoft\AiTutor\Integrations\{MissingActorResolver, MissingLmsAdapter};
use TDSoft\AiTutor\Licensing\UnavailableEntitlements;

final class AiTutorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/ai-tutor.php', 'ai-tutor');
        $this->app->bindIf(ActorResolver::class, MissingActorResolver::class);
        $this->app->bindIf(LmsContextAdapter::class, MissingLmsAdapter::class);
        $this->app->bindIf(Entitlements::class, UnavailableEntitlements::class);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([Core\SchemaCheckCommand::class]);
        }
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'ai-tutor');
        $this->publishes([__DIR__.'/../config/ai-tutor.php' => config_path('ai-tutor.php')], 'ai-tutor-config');
    }
}
