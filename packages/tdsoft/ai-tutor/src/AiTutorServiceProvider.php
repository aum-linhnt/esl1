<?php

namespace TDSoft\AiTutor;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Events\MigrationsStarted;
use Illuminate\Support\Carbon;
use Illuminate\Support\ServiceProvider;
use TDSoft\AiTutor\Contracts\ActorResolver;
use TDSoft\AiTutor\Contracts\Entitlements;
use TDSoft\AiTutor\Contracts\LicenseAdministrator;
use TDSoft\AiTutor\Contracts\LmsContextAdapter;
use TDSoft\AiTutor\Integrations\MissingActorResolver;
use TDSoft\AiTutor\Integrations\MissingLmsAdapter;
use TDSoft\AiTutor\Licensing\DenyLicenseAdministrator;
use TDSoft\AiTutor\Licensing\InstallationIdentity;
use TDSoft\AiTutor\Licensing\OfflineEntitlements;
use TDSoft\AiTutor\Licensing\RefreshLicenseJob;

final class AiTutorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/ai-tutor.php', 'ai-tutor');
        $this->app->bindIf(ActorResolver::class, MissingActorResolver::class);
        $this->app->bindIf(LmsContextAdapter::class, MissingLmsAdapter::class);
        $this->app->bindIf(Entitlements::class, OfflineEntitlements::class);
        $this->app->bindIf(LicenseAdministrator::class, DenyLicenseAdministrator::class);
        $this->app->bindIf(Contracts\KnowledgeAdministrator::class, Integrations\DenyKnowledgeAdministrator::class);
        $this->app->bindIf(Contracts\VectorStore::class, Knowledge\LocalVectorStore::class);
        $this->app->scoped(Core\StreamOutput::class);
        $this->app->bindIf(Contracts\BackgroundActor::class, Integrations\MissingBackgroundActor::class);
    }

    public function boot(): void
    {
        $this->app['events']->listen(MigrationsStarted::class, [Core\MigrationPreflight::class, 'handle']);
        if ($this->app->runningInConsole()) {
            $this->commands([Core\SchemaCheckCommand::class, Licensing\LicenseCommand::class]);
        }
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'ai-tutor');
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadRoutesFrom(__DIR__.'/../routes/tutor.php');
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            $schedule->job(new RefreshLicenseJob)->everyMinute()->name('ai-tutor-license-refresh')
                ->withoutOverlapping()->when(function () {
                    if (! config('ai-tutor.license.server_url')) {
                        return false;
                    }
                    $state = app(InstallationIdentity::class)->initialized();

                    return $state && $state->encrypted_envelope
                        && (! $state->next_attempt_at || now()->gte(Carbon::parse($state->next_attempt_at)));
                });
        });
        $this->publishes([__DIR__.'/../config/ai-tutor.php' => config_path('ai-tutor.php')], 'ai-tutor-config');
    }
}
