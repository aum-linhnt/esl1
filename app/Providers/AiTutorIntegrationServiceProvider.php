<?php

namespace App\Providers;

use App\Integrations\AiTutor\WebsiteActorResolver;
use App\Integrations\AiTutor\WebsiteLmsAdapter;
use Illuminate\Support\ServiceProvider;
use TDSoft\AiTutor\Contracts\ActorResolver;
use TDSoft\AiTutor\Contracts\LmsContextAdapter;

final class AiTutorIntegrationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ActorResolver::class, WebsiteActorResolver::class);
        $this->app->bind(LmsContextAdapter::class, WebsiteLmsAdapter::class);
    }
}
