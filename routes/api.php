<?php

use App\Http\Controllers\AvailableController;
use App\Http\Controllers\ContentController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\TreeController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post("/auth", [UserController::class, "auth"]);
Route::get("/resource/{content}", [ContentController::class, "getResource"]);
Route::get("/folder", [TreeController::class, "index"]);
Route::post("/getImage", [ContentController::class, "getImage"]);
Route::post("/getFile", [ContentController::class, "getFile"]);

Route::middleware(['auth:sanctum'])->group(function () {
    //admin,ceo,user
    Route::get("/userFolder", [TreeController::class, "userFolder"]);
    Route::get('/logout', [UserController::class, 'logout']);
    //admin,ceo
    Route::post("/resource", [ContentController::class, "saveResource"]);
    Route::post("/saveImageByUrl", [ContentController::class, "saveImageByUrl"]);
    Route::post("/saveImage", [ContentController::class, "saveImage"]);
    Route::post("/saveFile", [ContentController::class, "saveFile"]);
    Route::delete("/folder/{del}", [TreeController::class, "delete"]);
    Route::delete("/resourcedel/{content}", [ContentController::class, "delResource"]);
    Route::post("/folder", [TreeController::class, "store"]);
    Route::post("/doc/{operation}/{id}", [TreeController::class, "upanddown"]);
    //admin
    Route::post("/user", [UserController::class, "store"]);
    Route::put("/user/{id}", [UserController::class, "update"]);
    Route::delete("/user/{id}", [UserController::class, "delete"]);
    Route::get("/user", [UserController::class, "index"]);
    Route::get("/group", [GroupController::class, "index"]);
    Route::post("/group", [GroupController::class, "store"]);
    Route::put("/group/{id}", [GroupController::class, "update"]);
    Route::delete("/group/{id}", [GroupController::class, "delete"]);  

});
