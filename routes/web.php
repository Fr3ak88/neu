<?php

use App\Http\Controllers\AmazonAccountController;
use App\Http\Controllers\Admin;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FbaShipmentController;
use App\Http\Controllers\FbaInventoryController;
use App\Http\Controllers\JtlConnectionController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth;

// ── Öffentliche Routen ──────────────────────────────────────
Route::get('/login', [Auth\LoginController::class, 'show'])->name('login');
Route::post('/login', [Auth\LoginController::class, 'store']);
Route::get('/register', [Auth\RegisterController::class, 'show'])->name('register');
Route::post('/register', [Auth\RegisterController::class, 'store']);
Route::post('/logout', function () {
    auth()->logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect()->route('home');
})->name('logout');

// ── Webhooks (kein Auth, kein CSRF) ─────────────────────────
Route::post('/webhooks/storlogix', [\App\Http\Controllers\WebhookController::class, 'storlogix'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
    ->name('webhooks.storlogix');

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return view('welcome');
})->name('home');

// ── Kundenportal (eigenes Login) ────────────────────────────
Route::get('/customer/login', [\App\Http\Controllers\Auth\CustomerLoginController::class, 'show'])
    ->name('customer.login');
Route::post('/customer/login', [\App\Http\Controllers\Auth\CustomerLoginController::class, 'store']);
Route::post('/customer/logout', [\App\Http\Controllers\Auth\CustomerLoginController::class, 'logout'])
    ->name('customer.logout');

Route::middleware(['auth:customer'])->group(function () {
    Route::get('/customer', function () {
        $customer = auth('customer')->user();
        return view('customer.dashboard', compact('customer'));
    })->name('customer.dashboard');
});

// ── Geschützte Routen ───────────────────────────────────────
Route::middleware(['auth'])->group(function () {

    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // Amazon Accounts – lesen für alle, schreiben nur für Firmenadmin/Superadmin
    Route::get('amazon-accounts', [AmazonAccountController::class, 'index'])->name('amazon-accounts.index');
    Route::get('amazon-accounts/create', [AmazonAccountController::class, 'create'])->name('amazon-accounts.create')->middleware('role:firmenadmin,superadmin');
    Route::post('amazon-accounts', [AmazonAccountController::class, 'store'])->name('amazon-accounts.store')->middleware('role:firmenadmin,superadmin');
    Route::get('amazon-accounts/{amazonAccount}', [AmazonAccountController::class, 'show'])->name('amazon-accounts.show');
    Route::get('amazon-accounts/{amazonAccount}/edit', [AmazonAccountController::class, 'edit'])->name('amazon-accounts.edit')->middleware('role:firmenadmin,superadmin');
    Route::put('amazon-accounts/{amazonAccount}', [AmazonAccountController::class, 'update'])->name('amazon-accounts.update')->middleware('role:firmenadmin,superadmin');
    Route::delete('amazon-accounts/{amazonAccount}', [AmazonAccountController::class, 'destroy'])->name('amazon-accounts.destroy')->middleware('role:firmenadmin,superadmin');
    Route::post('amazon-accounts/{amazonAccount}/test-connection',
        [AmazonAccountController::class, 'testConnection']
    )->name('amazon-accounts.test-connection');
    Route::patch('amazon-accounts/{amazonAccount}/toggle-active',
        [AmazonAccountController::class, 'toggleActive']
    )->name('amazon-accounts.toggle-active')
        ->middleware('role:firmenadmin,superadmin');

    // FBA Plans & Amazon-Integration (VOR resource, damit /plans nicht als {fbaShipment} gefangen wird)
    Route::get('fba-shipments/plans', [FbaShipmentController::class, 'listPlans'])
        ->name('fba-shipments.plans');
    Route::get('fba-shipments/plans/{planId}', [FbaShipmentController::class, 'planDetail'])
        ->name('fba-shipments.plan-detail');

    // FBA Umlagerungen
    Route::resource('fba-shipments', FbaShipmentController::class)->parameters(['fba-shipments' => 'fbaShipment']);
    Route::post('fba-shipments/{fbaShipment}/create-plan',
        [FbaShipmentController::class, 'createPlan']
    )->name('fba-shipments.create-plan');
    Route::post('fba-shipments/{fbaShipment}/register',
        [FbaShipmentController::class, 'register']
    )->name('fba-shipments.register');
    Route::post('fba-shipments/{fbaShipment}/cancel',
        [FbaShipmentController::class, 'cancel']
    )->name('fba-shipments.cancel');
    Route::post('fba-shipments/{fbaShipment}/check-status',
        [FbaShipmentController::class, 'checkStatus']
    )->name('fba-shipments.check-status');
    Route::post('fba-shipments/{fbaShipment}/retry',
        [FbaShipmentController::class, 'retry']
    )->name('fba-shipments.retry');
    Route::patch('fba-shipments/{fbaShipment}/update-account',
        [FbaShipmentController::class, 'updateAccount']
    )->name('fba-shipments.update-account');
    Route::post('fba-shipments/import-jtl',
        [FbaShipmentController::class, 'importJtl']
    )->name('fba-shipments.import-jtl');

    // Karton-Handling
    Route::post('fba-shipments/{fbaShipment}/cartons',
        [FbaShipmentController::class, 'storeCarton']
    )->name('fba-shipments.store-carton');
    Route::delete('fba-shipments/{fbaShipment}/cartons/{carton}',
        [FbaShipmentController::class, 'destroyCarton']
    )->name('fba-shipments.destroy-carton');

    // Pallet-Handling
    Route::post('fba-shipments/{fbaShipment}/pallets',
        [FbaShipmentController::class, 'storePallet']
    )->name('fba-shipments.store-pallet');
    Route::delete('fba-shipments/{fbaShipment}/pallets/{pallet}',
        [FbaShipmentController::class, 'destroyPallet']
    )->name('fba-shipments.destroy-pallet');

    // Labels
    Route::get('fba-shipments/{fbaShipment}/labels',
        [FbaShipmentController::class, 'labels']
    )->name('fba-shipments.labels');

    // Item-Handling
    Route::put('fba-shipments/{fbaShipment}/items/{fbaShipmentItem}',
        [FbaShipmentController::class, 'updateItem']
    )->name('fba-shipments.update-item');
    Route::put('fba-shipments/{fbaShipment}/items',
        [FbaShipmentController::class, 'bulkUpdateItems']
    )->name('fba-shipments.bulk-update-items');

    // FBA Inventory
    Route::get('/fba-inventory', [FbaInventoryController::class, 'index'])
        ->name('fba-inventory.index');
    Route::post('/fba-inventory/sync', [FbaInventoryController::class, 'sync'])
        ->name('fba-inventory.sync');
    Route::get('/fba-inventory/sync-progress', [FbaInventoryController::class, 'syncProgress'])
        ->name('fba-inventory.sync-progress');

    // FBA Plans & Amazon-Integration (weitere Routen)
    Route::patch('fba-shipments/{fbaShipment}/shipment-name',
        [FbaShipmentController::class, 'updateShipmentName']
    )->name('fba-shipments.update-shipment-name');
    Route::get('fba-shipments/{fbaShipment}/amazon-items',
        [FbaShipmentController::class, 'amazonItems']
    )->name('fba-shipments.amazon-items');
    Route::post('fba-shipments/{fbaShipment}/purchase-shipment',
        [FbaShipmentController::class, 'purchaseShipment']
    )->name('fba-shipments.purchase-shipment');

    // JTL-Wawi Connect
    Route::get('/jtl-connect', [JtlConnectionController::class, 'show'])
        ->name('jtl-connect.show');
    Route::put('/jtl-connect', [JtlConnectionController::class, 'update'])
        ->name('jtl-connect.update');
    Route::post('/jtl-connect/test', [JtlConnectionController::class, 'test'])
        ->name('jtl-connect.test');

    // JTL-Wawi Cloud API Connect
    Route::get('/jtl-cloud', [App\Http\Controllers\JtlCloudApiController::class, 'show'])
        ->name('jtl-cloud.show');
    Route::put('/jtl-cloud', [App\Http\Controllers\JtlCloudApiController::class, 'configure'])
        ->name('jtl-cloud.configure');
    Route::post('/jtl-cloud/test', [App\Http\Controllers\JtlCloudApiController::class, 'test'])
        ->name('jtl-cloud.test');

    // JTL-Wawi Einstellungen (unified)
    Route::get('/jtl-settings', [App\Http\Controllers\JtlSettingsController::class, 'show'])
        ->name('jtl.settings');
    Route::put('/jtl-settings/db', [App\Http\Controllers\JtlSettingsController::class, 'saveDb'])
        ->name('jtl.settings.db.save');
    Route::post('/jtl-settings/db/test', [App\Http\Controllers\JtlSettingsController::class, 'testDb'])
        ->name('jtl.settings.db.test');
    Route::put('/jtl-settings/apikey', [App\Http\Controllers\JtlSettingsController::class, 'saveApiKey'])
        ->name('jtl.settings.apikey.save');
    Route::post('/jtl-settings/apikey/test', [App\Http\Controllers\JtlSettingsController::class, 'testApiKey'])
        ->name('jtl.settings.apikey.test');
    Route::put('/jtl-settings/cloud', [App\Http\Controllers\JtlSettingsController::class, 'saveCloud'])
        ->name('jtl.settings.cloud.save');
    Route::post('/jtl-settings/cloud/test', [App\Http\Controllers\JtlSettingsController::class, 'testCloud'])
        ->name('jtl.settings.cloud.test');

    // Storlogix Connect
    Route::get('/storlogix-connect', [\App\Http\Controllers\StorlogixConnectionController::class, 'show'])
        ->name('storlogix-connect.show');
    Route::put('/storlogix-connect', [\App\Http\Controllers\StorlogixConnectionController::class, 'update'])
        ->name('storlogix-connect.update');
    Route::post('/storlogix-connect/test', [\App\Http\Controllers\StorlogixConnectionController::class, 'test'])
        ->name('storlogix-connect.test');

    // E-Mail-Einstellungen
    Route::get('/settings/email', [\App\Http\Controllers\EmailSettingsController::class, 'edit'])
        ->name('email-settings.edit');
    Route::put('/settings/email', [\App\Http\Controllers\EmailSettingsController::class, 'update'])
        ->name('email-settings.update');
    Route::post('/settings/email/test', [\App\Http\Controllers\EmailSettingsController::class, 'test'])
        ->name('email-settings.test');

    // Profil
    Route::get('/profile', [\App\Http\Controllers\ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [\App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/tenant', [\App\Http\Controllers\ProfileController::class, 'updateTenant'])->name('profile.update-tenant');

    // Kunden
    Route::resource('customers', \App\Http\Controllers\CustomerController::class)->parameters(['customers' => 'customer']);

    // Rechnungen
    Route::resource('rechnungen', \App\Http\Controllers\RechnungController::class)->parameters(['rechnungen' => 'rechnung']);
    Route::get('rechnungen/{rechnung}/pdf', [\App\Http\Controllers\RechnungController::class, 'pdf'])->name('rechnungen.pdf');
    Route::get('rechnungen/{rechnung}/pdf/view', [\App\Http\Controllers\RechnungController::class, 'pdfView'])->name('rechnungen.pdf-view');
    Route::get('rechnungen/{rechnung}/storno-pdf', [\App\Http\Controllers\RechnungController::class, 'stornoPdf'])->name('rechnungen.storno-pdf');
    Route::get('rechnungen/{rechnung}/storno-pdf/view', [\App\Http\Controllers\RechnungController::class, 'stornoPdfView'])->name('rechnungen.storno-pdf-view');
    Route::post('rechnungen/{rechnung}/duplicate', [\App\Http\Controllers\RechnungController::class, 'duplicate'])->name('rechnungen.duplicate');
    Route::patch('rechnungen/{rechnung}/status', [\App\Http\Controllers\RechnungController::class, 'status'])->name('rechnungen.status');
    Route::post('rechnungen/{rechnung}/email', [\App\Http\Controllers\RechnungController::class, 'sendEmail'])->name('rechnungen.email');
    Route::post('rechnungen/{rechnung}/mahnung', [\App\Http\Controllers\RechnungController::class, 'sendMahnung'])->name('rechnungen.mahnung');
    Route::get('api/customers/{customer}', [\App\Http\Controllers\RechnungController::class, 'getCustomer'])->name('api.customers.get');

    // Aufträge (Wiederkehrende Rechnungen)
    Route::resource('auftraege', \App\Http\Controllers\RechnungAuftragController::class)->parameters(['auftraege' => 'auftrag']);
    Route::get('auftraege/{auftrag}/pdf', [\App\Http\Controllers\RechnungAuftragController::class, 'pdf'])->name('auftraege.pdf');
    Route::post('auftraege/{auftrag}/erstelle-jetzt', [\App\Http\Controllers\RechnungAuftragController::class, 'erstelleJetzt'])->name('auftraege.erstelle-jetzt');
    Route::patch('auftraege/{auftrag}/toggle', [\App\Http\Controllers\RechnungAuftragController::class, 'toggle'])->name('auftraege.toggle');
    Route::post('auftraege/{auftrag}/email', [\App\Http\Controllers\RechnungAuftragController::class, 'sendEmail'])->name('auftraege.email');

    // WMS
    Route::prefix('wms')->name('wms.')->group(function () {
        Route::get('/', \App\Http\Controllers\Wms\WmsDashboardController::class)->name('dashboard');
        Route::get('bestaende', [\App\Http\Controllers\Wms\WmsProductController::class, 'index'])->name('products.index');
        Route::resource('orders', \App\Http\Controllers\Wms\WmsOrderController::class)->except(['create', 'store'])->parameters(['orders' => 'order']);
        Route::resource('shipments', \App\Http\Controllers\Wms\WmsShipmentController::class)->except(['create', 'store'])->parameters(['shipments' => 'shipment']);
        Route::get('returns', [\App\Http\Controllers\Wms\WmsReturnController::class, 'index'])->name('returns.index');
        Route::get('returns/{return}', [\App\Http\Controllers\Wms\WmsReturnController::class, 'show'])->name('returns.show');
        Route::put('returns/{return}', [\App\Http\Controllers\Wms\WmsReturnController::class, 'update'])->name('returns.update');

        // JTL-Wawi Sync
        Route::get('sync', [\App\Http\Controllers\Wms\WmsSyncController::class, 'index'])->name('sync.index');
        Route::post('sync/items', [\App\Http\Controllers\Wms\WmsSyncController::class, 'syncItems'])->name('sync.items');
        Route::post('sync/stocks', [\App\Http\Controllers\Wms\WmsSyncController::class, 'syncStocks'])->name('sync.stocks');
        Route::post('sync/orders', [\App\Http\Controllers\Wms\WmsSyncController::class, 'syncOrders'])->name('sync.orders');
        Route::post('sync/stocks/push', [\App\Http\Controllers\Wms\WmsSyncController::class, 'pushStocks'])->name('sync.stocks.push');

        // Storlogix Sync
        Route::post('sync/storlogix/returns', [\App\Http\Controllers\Wms\WmsSyncController::class, 'syncStorlogixReturns'])->name('sync.storlogix.returns');
        Route::post('sync/storlogix/stock', [\App\Http\Controllers\Wms\WmsSyncController::class, 'syncStorlogixStock'])->name('sync.storlogix.stock');
        Route::post('sync/storlogix/articles', [\App\Http\Controllers\Wms\WmsSyncController::class, 'syncArticlesToStorlogix'])->name('sync.storlogix.articles');
        Route::post('sync/storlogix/goods-receipts', [\App\Http\Controllers\Wms\WmsSyncController::class, 'syncGoodsReceipts'])->name('sync.storlogix.goods-receipts');
        Route::post('sync/storlogix/stock-changes', [\App\Http\Controllers\Wms\WmsSyncController::class, 'syncStockChanges'])->name('sync.storlogix.stock-changes');
        Route::post('sync/storlogix/order-updates', [\App\Http\Controllers\Wms\WmsSyncController::class, 'syncOrderUpdates'])->name('sync.storlogix.order-updates');
        Route::post('orders/{order}/send-storlogix', [\App\Http\Controllers\Wms\WmsSyncController::class, 'sendDeliveryOrder'])->name('orders.send-storlogix');
    });

    // Firmen-Admin: Benutzer verwalten
    Route::prefix('team')->name('firmenadmin.')->middleware('role:firmenadmin,superadmin')->group(function () {
        Route::get('/', [\App\Http\Controllers\FirmenadminController::class, 'index'])->name('users.index');
        Route::get('/neu', [\App\Http\Controllers\FirmenadminController::class, 'create'])->name('users.create');
        Route::post('/', [\App\Http\Controllers\FirmenadminController::class, 'store'])->name('users.store');
        Route::get('/{user}/bearbeiten', [\App\Http\Controllers\FirmenadminController::class, 'edit'])->name('users.edit');
        Route::put('/{user}', [\App\Http\Controllers\FirmenadminController::class, 'update'])->name('users.update');
        Route::delete('/{user}', [\App\Http\Controllers\FirmenadminController::class, 'destroy'])->name('users.destroy');
    });
});

// ── Admin Routen ──────────────────────────────────────────
Route::prefix('admin')->name('admin.')->middleware(['auth', 'superadmin'])->group(function () {
    Route::get('/', [Admin\AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/system', [Admin\AdminController::class, 'system'])->name('system');
    Route::get('/users', [Admin\AdminUserController::class, 'index'])->name('users.index');
    Route::get('/users/{user}', [Admin\AdminUserController::class, 'show'])->name('users.show');
    Route::get('/users/{user}/edit', [Admin\AdminUserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [Admin\AdminUserController::class, 'update'])->name('users.update');
    Route::get('/users/{user}/modules', [Admin\AdminUserController::class, 'modules'])->name('users.modules');
    Route::put('/users/{user}/modules', [Admin\AdminUserController::class, 'updateModules'])->name('users.update-modules');
    Route::get('/tenants', [Admin\AdminTenantController::class, 'index'])->name('tenants.index');
    Route::get('/tenants/{tenant}', [Admin\AdminTenantController::class, 'show'])->name('tenants.show');
    Route::get('/tenants/{tenant}/edit', [Admin\AdminTenantController::class, 'edit'])->name('tenants.edit');
    Route::put('/tenants/{tenant}', [Admin\AdminTenantController::class, 'update'])->name('tenants.update');
    Route::delete('/tenants/{tenant}', [Admin\AdminTenantController::class, 'destroy'])->name('tenants.destroy');
});
