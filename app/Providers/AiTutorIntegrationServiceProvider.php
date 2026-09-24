<?php

namespace App\Providers;

use App\Integrations\AiTutor\WebsiteActorResolver;
use App\Integrations\AiTutor\WebsiteLicenseAdministrator;
use App\Integrations\AiTutor\WebsiteLmsAdapter;
use Illuminate\Support\ServiceProvider;
use TDSoft\AiTutor\Contracts\ActorResolver;
use TDSoft\AiTutor\Contracts\LicenseAdministrator;
use TDSoft\AiTutor\Contracts\LmsContextAdapter;

final class AiTutorIntegrationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ActorResolver::class, WebsiteActorResolver::class);
        $this->app->bind(LmsContextAdapter::class, WebsiteLmsAdapter::class);
        $this->app->bind(LicenseAdministrator::class, WebsiteLicenseAdministrator::class);
        $this->app->bind(\TDSoft\AiTutor\Contracts\KnowledgeAdministrator::class, \App\Integrations\AiTutor\WebsiteKnowledgeAdministrator::class);
        $this->app->bind(\TDSoft\AiTutor\Contracts\BackgroundActor::class, \App\Integrations\AiTutor\WebsiteBackgroundActor::class);
    }

    public function boot(): void
    {
        config([
            'ai-tutor.license.admin_layout' => 'layouts.admin',
            'ai-tutor.license.admin_theme' => 'dark',
            'ai-tutor.ui.asset_entries' => ['resources/js/ai-tutor.js', 'resources/scss/ai-tutor.scss'],
        ]);
        if (! config('ai-tutor.license.asset_entries')) {
            config(['ai-tutor.license.asset_entries' => ['resources/scss/ai-tutor.scss']]);
        }
    }
}
