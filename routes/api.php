<?php

use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

// Webhooks - No authentication required
Route::prefix('webhooks')->group(function () {
    Route::post('/whatsapp/status', [WebhookController::class, 'whatsappStatus']);
});
