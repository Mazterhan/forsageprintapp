<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Routing\Route;

final class PageBackNavigation
{
    public function url(Request $request): ?string
    {
        $currentRoute = $request->route();
        if (! $currentRoute instanceof Route) {
            return null;
        }

        return match ($currentRoute->getName()) {
            'orders.create',
            'orders.calculation',
            'orders.proposals',
            'orders.clients.index' => route('orders.index'),

            'orders.show' => route('orders.index'),
            'orders.edit' => route('orders.show', $currentRoute->parameter('order')),
            'orders.proposals.show' => route('orders.proposals'),

            'orders.clients.create',
            'orders.clients.show' => route('orders.clients.index'),
            'orders.clients.edit' => route('orders.clients.show', $currentRoute->parameter('client')),

            'price.create',
            'price.show' => route('price.index'),

            'admin.users.index',
            'admin.departments.index',
            'admin.editgroupsandcategories' => route('admin.index'),

            'admin.users.create',
            'admin.users.edit',
            'admin.roles.create',
            'admin.roles.edit' => route('admin.users.index'),

            'admin.departments.create',
            'admin.departments.edit' => route('admin.departments.index'),

            'admin.product-categories.index',
            'admin.product-types.index',
            'admin.set-flm.index' => route('admin.editgroupsandcategories'),

            default => null,
        };
    }
}
