<?php

namespace Tests\Unit;

use App\Support\PageBackNavigation;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Str;
use Tests\TestCase;

class PageBackNavigationTest extends TestCase
{
    public function test_nested_application_pages_have_explicit_parent_urls(): void
    {
        $orderId = (string) Str::uuid();
        $clientId = (string) Str::uuid();

        $cases = [
            [route('orders.create'), route('orders.index')],
            [route('orders.calculation'), route('orders.index')],
            [route('orders.proposals'), route('orders.index')],
            [route('orders.proposals.show', Str::uuid()), route('orders.proposals')],
            [route('orders.show', $orderId), route('orders.index')],
            [route('orders.edit', $orderId), route('orders.show', $orderId)],
            [route('orders.clients.index'), route('orders.index')],
            [route('orders.clients.create'), route('orders.clients.index')],
            [route('orders.clients.show', $clientId), route('orders.clients.index')],
            [route('orders.clients.edit', $clientId), route('orders.clients.show', $clientId)],
            [route('price.create'), route('price.index')],
            [route('price.show', Str::uuid()), route('price.index')],
            [route('admin.users.index'), route('admin.index')],
            [route('admin.users.create'), route('admin.users.index')],
            [route('admin.users.edit', Str::uuid()), route('admin.users.index')],
            [route('admin.roles.create'), route('admin.users.index')],
            [route('admin.roles.edit', Str::uuid()), route('admin.users.index')],
            [route('admin.departments.index'), route('admin.index')],
            [route('admin.departments.create'), route('admin.departments.index')],
            [route('admin.departments.edit', Str::uuid()), route('admin.departments.index')],
            [route('admin.editgroupsandcategories'), route('admin.index')],
            [route('admin.product-categories.index'), route('admin.editgroupsandcategories')],
            [route('admin.product-types.index'), route('admin.editgroupsandcategories')],
            [route('admin.set-flm.index'), route('admin.editgroupsandcategories')],
        ];

        foreach ($cases as [$currentUrl, $expectedParentUrl]) {
            $this->assertSame($expectedParentUrl, $this->backUrlFor($currentUrl), $currentUrl);
        }
    }

    public function test_first_level_pages_do_not_have_a_back_url(): void
    {
        foreach ([
            route('orders.index'),
            route('price.index'),
            route('dashboard'),
            route('admin.index'),
            route('profile.edit'),
        ] as $url) {
            $this->assertNull($this->backUrlFor($url), $url);
        }
    }

    private function backUrlFor(string $url): ?string
    {
        $request = Request::create($url);
        $route = app('router')->getRoutes()->match($request);
        $route->bind($request);
        $request->setRouteResolver(static fn (): Route => $route);

        return app(PageBackNavigation::class)->url($request);
    }
}
