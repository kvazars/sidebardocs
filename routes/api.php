<?php

use App\Http\Controllers\ContentController;
use App\Http\Controllers\TreeController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::post("/saveImage", [ContentController::class, "saveImage"]);
Route::post("/saveFile", [ContentController::class, "saveFile"]);
Route::post("/saveImageByUrl", [ContentController::class, "saveImageByUrl"]);
Route::post("/getImage", [ContentController::class, "getImage"]);
Route::post("/getFile", [ContentController::class, "getFile"]);
Route::post("/resource", [ContentController::class, "saveResource"]);
// Route::post("/saveResource", [ContentController::class, "saveResource"]);

Route::get("/resource/{content}", [ContentController::class, "getResource"]);
Route::delete("/resource/{content}", [ContentController::class, "delResource"]);

Route::post("/folder", [TreeController::class,"store"]);



Route::get("/folder", [TreeController::class,"index"]);
Route::get("/userFolder", [TreeController::class,"index"]);


Route::delete("/folder/{del}", [TreeController::class,"delete"]);

Route::post("reg", [UserController::class,"reg"]);
Route::post("auth", [UserController::class,"auth"]);