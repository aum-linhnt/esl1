<?php

namespace App\Providers;

use App\Integrations\AiTutor\{WebsiteActorResolver, WebsiteLmsAdapter};
use Illuminate\Support\ServiceProvider;
use TDSoft\AiTutor\Contracts\{ActorResolver, LmsContextAdapter};

final class AiTutorIntegrationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ActorResolver::class, WebsiteActorResolver::class);
        $this->app->bind(LmsContextAdapter::class, WebsiteLmsAdapter::class);
    }
}
