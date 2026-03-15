<?php

use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\EditGroupsAndCategoriesController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Orders\ClientController;
use App\Http\Controllers\Orders\OrderController;
use App\Http\Controllers\Orders\ProductTypeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Price\PriceController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'role:admin|manager'])->group(function () {
    Route::get('/admin', function () {
        $sort = (string) request()->query('sort', '');
        $direction = strtolower((string) request()->query('direction', 'asc')) === 'desc' ? 'desc' : 'asc';

        $sortMap = [
            'user' => 'users.name',
            'department' => 'departments.name',
            'direction' => 'department_categories.name',
            'position' => 'department_positions.name',
            'role' => 'users.role',
        ];

        $activeUsers = \App\Models\User::query()
            ->where('is_active', true)
            ->leftJoin('departments', 'departments.id', '=', 'users.department_id')
            ->leftJoin('department_categories', 'department_categories.id', '=', 'users.department_category_id')
            ->leftJoin('department_positions', 'department_positions.id', '=', 'users.department_position_id')
            ->select('users.*')
            ->with(['department', 'departmentCategory', 'position'])
            ->when(isset($sortMap[$sort]), function ($query) use ($sortMap, $sort, $direction) {
                $query->orderBy($sortMap[$sort], $direction);
            }, function ($query) {
                $query->orderBy('users.name');
            })
            ->get();

        return view('admin.index', [
            'activeUsers' => $activeUsers,
        ]);
    })->name('admin.index');
});

Route::middleware(['auth', 'role:admin|manager'])
    ->prefix('orders')
    ->name('orders.')
    ->group(function () {
        Route::get('/', [OrderController::class, 'index'])->name('index');
        Route::get('/calculation', [OrderController::class, 'calculation'])->name('calculation');
        Route::get('/proposals', [OrderController::class, 'saved'])->name('proposals');
        Route::get('/product-types', fn () => redirect()->route('admin.product-types.index'))->name('product-types.index');
        Route::post('/product-types', fn () => redirect()->route('admin.product-types.index'))->name('product-types.store');
        Route::get('/clients', [ClientController::class, 'index'])->name('clients.index');
        Route::get('/clients/create', [ClientController::class, 'create'])->name('clients.create');
        Route::post('/clients', [ClientController::class, 'store'])->name('clients.store');
        Route::get('/clients/{client}/edit', [ClientController::class, 'edit'])->name('clients.edit');
        Route::patch('/clients/{client}', [ClientController::class, 'update'])->name('clients.update');
        Route::patch('/clients/{client}/deactivate', [ClientController::class, 'deactivate'])->name('clients.deactivate');
    });

Route::middleware('auth')
    ->prefix('price')
    ->name('price.')
    ->group(function () {
        Route::get('/', [PriceController::class, 'index'])->name('index');
        Route::patch('/bulk-update', [PriceController::class, 'bulkUpdate'])->name('bulk-update');
        Route::get('/{priceItem}', [PriceController::class, 'show'])->name('show');
        Route::patch('/{priceItem}', [PriceController::class, 'update'])->name('update');
        Route::post('/{priceItem}/history/{history}/revert', [PriceController::class, 'revertHistory'])->name('history.revert');
        Route::patch('/{priceItem}/toggle', [PriceController::class, 'toggle'])->name('toggle');
        Route::patch('/{priceItem}/hide', [PriceController::class, 'hide'])->name('hide');
    });

Route::middleware('auth')->group(function () {
    Route::get('/new_item', [PriceController::class, 'create'])->name('price.create');
    Route::post('/new_item', [PriceController::class, 'store'])->name('price.store');
});

Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::patch('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::patch('/users/{user}/toggle-active', [UserController::class, 'toggleActive'])->name('users.toggle');

        Route::get('/departments', [DepartmentController::class, 'index'])->name('departments.index');
        Route::get('/departments/create', [DepartmentController::class, 'create'])->name('departments.create');
        Route::post('/departments', [DepartmentController::class, 'store'])->name('departments.store');
        Route::get('/departments/{department}/edit', [DepartmentController::class, 'edit'])->name('departments.edit');
        Route::patch('/departments/{department}', [DepartmentController::class, 'update'])->name('departments.update');
        Route::view('/editgroupsandcategories', 'admin.editgroupsandcategories')->name('editgroupsandcategories');
        Route::get('/editgroupsandcategories/product-categories', [EditGroupsAndCategoriesController::class, 'productCategories'])->name('product-categories.index');
        Route::post('/editgroupsandcategories/product-categories', [EditGroupsAndCategoriesController::class, 'storeProductCategories'])->name('product-categories.store');
        Route::get('/editgroupsandcategories/product-types', [ProductTypeController::class, 'index'])->name('product-types.index');
        Route::post('/editgroupsandcategories/product-types', [ProductTypeController::class, 'store'])->name('product-types.store');
    });

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
