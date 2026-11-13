<?php

use Cemarta7\StatamicLocalDomains\UseLocalSiteUrl;
use Illuminate\Container\Container;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Facade;
use PHPUnit\Framework\TestCase;
use Statamic\Facades\Site;

final class UseLocalSiteUrlTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $app = new Application();
        $app->instance('env', 'local');
        Container::setInstance($app);
        Facade::setFacadeApplication($app);
        Site::swap(Mockery::mock(\Statamic\Sites\Sites::class));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication(null);
        Container::setInstance(null);
        parent::tearDown();
    }

    public function test_local_request_preserves_path_and_restores_original_expression(): void
    {
        $site = Mockery::mock();
        Site::shouldReceive('hasMultiple')->once()->andReturn(false);
        Site::shouldReceive('default')->once()->andReturn($site);
        $site->shouldReceive('rawConfig')->once()->andReturn(['url' => '{{ config:app:url }}/en/']);
        $site->shouldReceive('url')->once()->andReturn('https://somnio.test/en/');
        $site->shouldReceive('set')->once()->with('url', '/en/')->ordered();
        $site->shouldReceive('set')->once()->with('url', '{{ config:app:url }}/en/')->ordered();

        $request = Request::create('https://somnio.remote.test/en/');
        $result = (new UseLocalSiteUrl())->handle($request, function ($actual) use ($request) {
            $this->assertSame($request, $actual);

            return 'page';
        });

        $this->assertSame('page', $result);
    }

    public function test_production_does_not_touch_statamic(): void
    {
        app()->instance('env', 'production');
        Site::shouldReceive('hasMultiple')->never();
        Site::shouldReceive('default')->never();

        $this->assertSame('page', (new UseLocalSiteUrl())->handle(
            Request::create('https://example.com'),
            fn () => 'page',
        ));
    }

    public function test_multiple_sites_are_not_changed(): void
    {
        Site::shouldReceive('hasMultiple')->once()->andReturn(true);
        Site::shouldReceive('default')->never();

        $this->assertSame('page', (new UseLocalSiteUrl())->handle(
            Request::create('https://somnio.remote.test'),
            fn () => 'page',
        ));
    }

    public function test_control_panel_does_not_change_site_settings(): void
    {
        $request = Request::create('https://somnio.remote.test/cp/sites');
        $request->setRouteResolver(fn () => (new Route('POST', 'cp/sites', fn () => null))
            ->name('statamic.cp.sites.update'));
        Site::shouldReceive('hasMultiple')->never();
        Site::shouldReceive('default')->never();

        $this->assertSame('saved', (new UseLocalSiteUrl())->handle($request, fn () => 'saved'));
    }

    public function test_site_url_is_restored_when_request_fails(): void
    {
        $site = Mockery::mock();
        Site::shouldReceive('hasMultiple')->once()->andReturn(false);
        Site::shouldReceive('default')->once()->andReturn($site);
        $site->shouldReceive('rawConfig')->once()->andReturn(['url' => 'https://somnio.test']);
        $site->shouldReceive('url')->once()->andReturn('https://somnio.test');
        $site->shouldReceive('set')->once()->with('url', '/')->ordered();
        $site->shouldReceive('set')->once()->with('url', 'https://somnio.test')->ordered();

        $this->expectException(RuntimeException::class);
        (new UseLocalSiteUrl())->handle(
            Request::create('https://somnio.remote.test'),
            fn () => throw new RuntimeException('Request failed'),
        );
    }
}
