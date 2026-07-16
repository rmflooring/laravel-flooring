<?php

use App\Http\Controllers\Api\AgentInboundEmailController;
use App\Http\Controllers\Api\IncomingLeadController;
use App\Http\Controllers\Api\ShopApiController;
use Illuminate\Support\Facades\Route;

Route::post('leads/incoming', [IncomingLeadController::class, 'receive'])
    ->middleware('api.key');

Route::post('agent/inbound-email', [AgentInboundEmailController::class, 'receive'])
    ->middleware('api.key:AGENT_INBOUND_API_KEY');

Route::prefix('shop')->group(function () {
    Route::middleware('throttle:60,1')->group(function () {
        Route::get('product-types', [ShopApiController::class, 'productTypes']);
        Route::get('product-lines', [ShopApiController::class, 'productLines']);
        Route::get('product-lines/{id}', [ShopApiController::class, 'productLineShow'])
            ->whereNumber('id');
        Route::get('product-feed', [ShopApiController::class, 'productFeed']);
    });

    Route::middleware('throttle:10,1')->group(function () {
        Route::post('quote-request', [ShopApiController::class, 'quoteRequest']);
        Route::post('sample-request', [ShopApiController::class, 'sampleRequest']);
    });
});
