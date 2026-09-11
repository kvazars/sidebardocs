<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('layouts.app');
});

Route::get('/export-resource/{id}', [App\Http\Controllers\ContentController::class, 'exportResource'])
    ->middleware(['auth:sanctum']);

Route::get('/{any}', function () {
    return view('layouts.app');
})->where('any', '^(?!api).*$');

