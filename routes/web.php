<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MechanicController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\WorkOrderController;
use App\Http\Controllers\WorkOrderItemController;
use App\Http\Controllers\WorkshopSelectionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::middleware('guest')->group(function (): void {
    Route::view('/register', 'auth.register')->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])->middleware('throttle:registration');

    Route::view('/login', 'auth.login')->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:auth-requests');

    Route::view('/forgot-password', 'auth.forgot-password')->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])
        ->middleware('throttle:password-requests')->name('password.email');
    Route::get('/reset-password/{token}', function (Request $request, string $token) {
        return view('auth.reset-password', ['token' => $token, 'email' => $request->query('email')]);
    })->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])
        ->middleware('throttle:password-requests')->name('password.update');
});

Route::middleware(['auth', 'auth.session'])->group(function (): void {
    Route::get('/workshops/select', [WorkshopSelectionController::class, 'index'])->name('workshops.select');
    Route::post('/workshops/active', [WorkshopSelectionController::class, 'store'])->name('workshops.activate');
    Route::get('/dashboard', DashboardController::class)->middleware('active.workshop')->name('dashboard');
    Route::middleware('active.workshop')->group(function (): void {
        foreach (['services', 'spare-parts'] as $catalogPrefix) {
            Route::get('/'.$catalogPrefix, [CatalogController::class, 'index'])->name($catalogPrefix.'.index');
            Route::get('/'.$catalogPrefix.'/create', [CatalogController::class, 'create'])->name($catalogPrefix.'.create');
            Route::post('/'.$catalogPrefix, [CatalogController::class, 'store'])->name($catalogPrefix.'.store');
            Route::get('/'.$catalogPrefix.'/{catalog}', [CatalogController::class, 'show'])->whereNumber('catalog')->name($catalogPrefix.'.show');
            Route::get('/'.$catalogPrefix.'/{catalog}/edit', [CatalogController::class, 'edit'])->whereNumber('catalog')->name($catalogPrefix.'.edit');
            Route::put('/'.$catalogPrefix.'/{catalog}', [CatalogController::class, 'update'])->whereNumber('catalog')->name($catalogPrefix.'.update');
            Route::patch('/'.$catalogPrefix.'/{catalog}/activation', [CatalogController::class, 'activation'])->whereNumber('catalog')->name($catalogPrefix.'.activation');
        }
        Route::post('/spare-parts/{catalog}/movements', [StockController::class, 'store'])->whereNumber('catalog')->name('spare-parts.movements');
        Route::post('/spare-parts/{catalog}/usages/{movement}/returns', [StockController::class, 'returns'])->whereNumber(['catalog', 'movement'])->name('spare-parts.returns');
        foreach (['services', 'parts'] as $itemPrefix) {
            Route::get('/work-orders/{work_order}/'.$itemPrefix.'/create', [WorkOrderItemController::class, 'create'])->whereNumber('work_order')->name('work-orders.'.$itemPrefix.'.create');
            Route::post('/work-orders/{work_order}/'.$itemPrefix, [WorkOrderItemController::class, 'store'])->whereNumber('work_order')->name('work-orders.'.$itemPrefix.'.store');
            Route::get('/work-orders/{work_order}/'.$itemPrefix.'/{line}/edit', [WorkOrderItemController::class, 'edit'])->whereNumber(['work_order', 'line'])->name('work-orders.'.$itemPrefix.'.edit');
            Route::put('/work-orders/{work_order}/'.$itemPrefix.'/{line}', [WorkOrderItemController::class, 'update'])->whereNumber(['work_order', 'line'])->name('work-orders.'.$itemPrefix.'.update');
            Route::post('/work-orders/{work_order}/'.$itemPrefix.'/{line}/cancel', [WorkOrderItemController::class, 'cancel'])->whereNumber(['work_order', 'line'])->name('work-orders.'.$itemPrefix.'.cancel');
        }
        Route::post('/work-orders/{work_order}/parts/{line}/use', [WorkOrderItemController::class, 'usePart'])->whereNumber(['work_order', 'line'])->name('work-orders.parts.use');
        Route::post('/work-orders/{work_order}/parts/{line}/returns', [WorkOrderItemController::class, 'returnPart'])->whereNumber(['work_order', 'line'])->name('work-orders.parts.return');
        Route::get('/mechanics', [MechanicController::class, 'index'])->name('mechanics.index');
        Route::post('/mechanics', [MechanicController::class, 'store'])->name('mechanics.store');
        Route::post('/mechanics/{mechanic}/resend', [MechanicController::class, 'resend'])->whereNumber('mechanic')->middleware('throttle:password-requests')->name('mechanics.resend');
        Route::patch('/mechanics/{mechanic}/activation', [MechanicController::class, 'activation'])->whereNumber('mechanic')->name('mechanics.activation');
        Route::post('/work-orders/{work_order}/transition', [WorkOrderController::class, 'transition'])->whereNumber('work_order')->name('work-orders.transition');
        Route::put('/work-orders/{work_order}/mechanic', [WorkOrderController::class, 'assign'])->whereNumber('work_order')->name('work-orders.assign');
        Route::resource('work-orders', WorkOrderController::class)->except('destroy')->whereNumber('work_order');
        Route::resource('customers', CustomerController::class)->except('destroy')->whereNumber('customer');
        Route::get('/vehicles/lookup', [VehicleController::class, 'lookup'])->name('vehicles.lookup');
        Route::get('/vehicles/customer-options', [VehicleController::class, 'customers'])->name('vehicles.customers');
        Route::resource('vehicles', VehicleController::class)->except('destroy')->whereNumber('vehicle');
    });
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});
