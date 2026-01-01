<?php

use App\Http\Controllers\Api\KodeposController;

Route::get('/kodepos/search', [KodeposController::class, 'search']);
