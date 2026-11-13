<?php

namespace Cemarta7\StatamicLocalDomains;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;

class LocalDomainsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->booted(function (): void {
            $this->app->make(Router::class)->prependMiddlewareToGroup(
                'web',
                UseLocalSiteUrl::class,
            );
        });
    }
}
