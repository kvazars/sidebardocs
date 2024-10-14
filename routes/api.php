<?php

use App\Http\Controllers\ContentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::post("/saveImage", [ContentController::class, "saveImage"]);
Route::post("/saveImageByUrl", [ContentController::class, "saveImageByUrl"]);
