<?php

namespace Cemarta7\StatamicLocalDomains;

use Closure;
use Illuminate\Http\Request;
use Statamic\Facades\Site;

class UseLocalSiteUrl
{
    public function handle(Request $request, Closure $next): mixed
    {
        if (! app()->environment('local') || $request->routeIs('statamic.cp.*') || Site::hasMultiple()) {
            return $next($request);
        }

        $site = Site::default();
        $originalUrl = $site->rawConfig()['url'];
        $path = parse_url($site->url(), PHP_URL_PATH) ?: '/';

        $site->set('url', $path);

        try {
            return $next($request);
        } finally {
            $site->set('url', $originalUrl);
        }
    }
}
