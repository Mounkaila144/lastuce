<?php

use App\Http\Controllers\SearchController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Story S3.5 — Endpoint suggestions léger pour l'autocomplete header.
Route::get('/search/suggestions', [SearchController::class, 'suggestions'])
    ->middleware('throttle:60,1')
    ->name('api.search.suggestions');
