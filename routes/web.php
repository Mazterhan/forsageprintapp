<?php

use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Orders\ClientController;
use App\Http\Controllers\Orders\OrderController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Pricing\PricingController;
use App\Http\Controllers\Pricing\PricingItemController;
use App\Http\Controllers\Pricing\SubcontractorController;
use App\Http\Controllers\Purchases\PurchaseController;
use App\Http\Controllers\Purchases\PurchaseImportController;
use App\Http\Controllers\Purchases\PurchaseReviewController;
use App\Http\Controllers\Purchases\SupplierController;
use App\Http\Controllers\Tariffs\TariffController;
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
        Route::get('/clients', [ClientController::class, 'index'])->name('clients.index');
        Route::get('/clients/create', [ClientController::class, 'create'])->name('clients.create');
        Route::post('/clients', [ClientController::class, 'store'])->name('clients.store');
        Route::get('/clients/{client}/edit', [ClientController::class, 'edit'])->name('clients.edit');
        Route::patch('/clients/{client}', [ClientController::class, 'update'])->name('clients.update');
        Route::patch('/clients/{client}/deactivate', [ClientController::class, 'deactivate'])->name('clients.deactivate');
    });

Route::middleware(['auth', 'role:admin|manager'])
    ->prefix('purchases')
    ->name('purchases.')
    ->group(function () {
        Route::get('/', [PurchaseController::class, 'index'])->name('index');
        Route::get('/suppliers', [SupplierController::class, 'index'])->name('suppliers.index');
        Route::get('/suppliers/create', [SupplierController::class, 'create'])->name('suppliers.create');
        Route::post('/suppliers', [SupplierController::class, 'store'])->name('suppliers.store');
        Route::get('/suppliers/{supplier}', [SupplierController::class, 'show'])->name('suppliers.show');
        Route::patch('/suppliers/{supplier}', [SupplierController::class, 'update'])->name('suppliers.update');
        Route::patch('/suppliers/{supplier}/toggle-active', [SupplierController::class, 'toggleActive'])->name('suppliers.toggle');
        Route::post('/suppliers/{supplier}/documents', [SupplierController::class, 'storeDocument'])->name('suppliers.documents.store');
        Route::get('/suppliers/documents/{document}', [SupplierController::class, 'downloadDocument'])->name('suppliers.documents.download');
        Route::get('/import_file', [PurchaseImportController::class, 'create'])->name('import.create');
        Route::get('/import_template', [PurchaseImportController::class, 'downloadTemplate'])->name('import.template');
        Route::get('/import_template_xlsx', [PurchaseImportController::class, 'downloadTemplateXlsx'])->name('import.template.xlsx');
        Route::post('/import_file', [PurchaseImportController::class, 'store'])->name('import.store');
        Route::get('/{purchase}/review', [PurchaseReviewController::class, 'show'])->name('review');
    });

Route::middleware(['auth', 'role:admin|manager'])
    ->prefix('pricing')
    ->name('pricing.')
    ->group(function () {
        Route::get('/', [PricingController::class, 'index'])->name('index');
        Route::post('/apply', [PricingController::class, 'applyBulk'])->name('apply.bulk');
        Route::post('/items/{pricingItem}/apply', [PricingController::class, 'applySingle'])->name('apply.single');
        Route::post('/items/{pricingItem}/deactivate', [PricingController::class, 'deactivate'])->name('items.deactivate');
        Route::get('/items/{pricingItem}', [PricingItemController::class, 'show'])->name('items.show');
        Route::patch('/items/{pricingItem}', [PricingItemController::class, 'update'])->name('items.update');

        Route::get('/subcontractors', [SubcontractorController::class, 'index'])->name('subcontractors.index');
        Route::get('/subcontractors/create', [SubcontractorController::class, 'create'])->name('subcontractors.create');
        Route::post('/subcontractors', [SubcontractorController::class, 'store'])->name('subcontractors.store');
        Route::get('/subcontractors/{subcontractor}', [SubcontractorController::class, 'edit'])->name('subcontractors.edit');
        Route::patch('/subcontractors/{subcontractor}', [SubcontractorController::class, 'update'])->name('subcontractors.update');
        Route::patch('/subcontractors/{subcontractor}/toggle', [SubcontractorController::class, 'toggle'])->name('subcontractors.toggle');
    });

Route::middleware('auth')
    ->prefix('tariffs')
    ->name('tariffs.')
    ->group(function () {
        Route::get('/', [TariffController::class, 'index'])->name('index');
        Route::get('/{tariff}', [TariffController::class, 'show'])->name('show');
        Route::patch('/{tariff}', [TariffController::class, 'update'])->name('update');
        Route::patch('/{tariff}/deactivate', [TariffController::class, 'deactivate'])->name('deactivate');
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
    });

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
