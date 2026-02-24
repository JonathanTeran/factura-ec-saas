<?php

use App\Http\Controllers\Portal\PortalAuthController;
use App\Livewire\Portal\PortalDashboard;
use App\Livewire\Portal\PortalDocumentList;
use App\Livewire\Portal\PortalDocumentShow;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Portal Routes
|--------------------------------------------------------------------------
|
| Rutas del portal de clientes. Los clientes finales (quienes reciben
| facturas) acceden via magic link para ver/descargar sus documentos.
|
*/

Route::prefix('portal')->name('portal.')->group(function () {

    // Rutas publicas (sin autenticacion de portal)
    Route::get('login', [PortalAuthController::class, 'showLogin'])->name('login');
    Route::post('login', [PortalAuthController::class, 'sendMagicLink'])->name('login.send');
    Route::get('auth/{token}', [PortalAuthController::class, 'authenticate'])->name('auth');
    Route::get('link-sent', [PortalAuthController::class, 'linkSent'])->name('link-sent');

    // Rutas protegidas (requieren sesion de portal)
    Route::middleware('portal.auth')->group(function () {
        Route::get('/', PortalDashboard::class)->name('dashboard');
        Route::get('documents', PortalDocumentList::class)->name('documents.index');
        Route::get('documents/{document}', PortalDocumentShow::class)->name('documents.show');
        Route::get('documents/{document}/ride', [PortalAuthController::class, 'downloadRide'])->name('documents.ride');
        Route::get('documents/{document}/xml', [PortalAuthController::class, 'downloadXml'])->name('documents.xml');
        Route::post('logout', [PortalAuthController::class, 'logout'])->name('logout');
    });
});
