<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('layouts.app');
});


Route::get('/export-resource/{id}', [App\Http\Controllers\ContentController::class, 'downloadResource'])
    ->middleware(['auth:sanctum'])
    ->name('resource.download');

Route::get('/{any}', function () {
    return view('layouts.app');
})->where('any', '^(?!api).*$');

