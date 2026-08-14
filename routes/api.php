<?php

use App\Http\Controllers\AmazonAccountController;
use App\Http\Controllers\FbaShipmentController;
use App\Http\Controllers\FbaInventoryController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function () {

    // Amazon Accounts – lesen für alle, schreiben nur für Firmenadmin/Superadmin
    Route::get('amazon-accounts', [AmazonAccountController::class, 'index']);
    Route::post('amazon-accounts', [AmazonAccountController::class, 'store'])->middleware('role:firmenadmin,superadmin');
    Route::get('amazon-accounts/{account}', [AmazonAccountController::class, 'show']);
    Route::put('amazon-accounts/{account}', [AmazonAccountController::class, 'update'])->middleware('role:firmenadmin,superadmin');
    Route::delete('amazon-accounts/{account}', [AmazonAccountController::class, 'destroy'])->middleware('role:firmenadmin,superadmin');
    Route::post('amazon-accounts/{account}/test-connection', [AmazonAccountController::class, 'testConnection']);

    // FBA Umlagerungen
    Route::apiResource('fba-shipments', FbaShipmentController::class);
    Route::post('fba-shipments/{shipment}/create-plan',   [FbaShipmentController::class, 'createPlan']);
    Route::post('fba-shipments/{shipment}/register',      [FbaShipmentController::class, 'register']);
    Route::post('fba-shipments/{shipment}/cancel',        [FbaShipmentController::class, 'cancel']);
    Route::get( 'fba-shipments/{shipment}/labels',        [FbaShipmentController::class, 'labels']);
    Route::get( 'fba-shipments/{shipment}/check-status',  [FbaShipmentController::class, 'checkStatus']);

    // Carton-Handling
    Route::post('fba-shipments/{shipment}/cartons',       [FbaShipmentController::class, 'storeCarton']);
    Route::delete('fba-shipments/{shipment}/cartons/{carton}', [FbaShipmentController::class, 'destroyCarton']);

    // Pallet-Handling
    Route::post('fba-shipments/{shipment}/pallets',       [FbaShipmentController::class, 'storePallet']);
    Route::delete('fba-shipments/{shipment}/pallets/{pallet}', [FbaShipmentController::class, 'destroyPallet']);

    // FBA Bestand
    Route::get('fba-inventory',                           [FbaInventoryController::class, 'index']);
    Route::post('fba-inventory/sync',                     [FbaInventoryController::class, 'sync']);
});
