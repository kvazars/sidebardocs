<?php

use App\Http\Controllers\AboutController;
use App\Http\Controllers\AvailableController;
use App\Http\Controllers\ContentController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\TreeController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post("/auth", [UserController::class, "auth"]);
Route::get("/resource/{content}", [ContentController::class, "getResource"]);
Route::get("/homepage", [TreeController::class, "index"]);

Route::middleware(['auth:sanctum'])->group(function () {
    //admin,ceo,user
    Route::get("/userFolder", [TreeController::class, "userFolder"]);
    Route::get('/logout', [UserController::class, 'logout']);
    Route::get("/resourceauth/{content}", [ContentController::class, "getResource"]);

    //admin,ceo
    Route::middleware(["role:admin|ceo"])->group(function () {
        Route::post("/resource", [ContentController::class, "saveResource"]);
        Route::post("/saveImageByUrl", [ContentController::class, "saveImageByUrl"]);
        Route::post("/saveImage", [ContentController::class, "saveImage"]);
        Route::post("/saveFile", [ContentController::class, "saveFile"]);
        Route::delete("/folder/{del}", [TreeController::class, "delete"]);
        Route::delete("/resourcedel/{content}", [ContentController::class, "delResource"]);
        Route::post("/saveresourceadmin", [TreeController::class, "saveresourceadmin"]);
        Route::post("/folder", [TreeController::class, "store"]);
        Route::post("/doc/{operation}/{id}", [TreeController::class, "upanddown"]);
        Route::get('/getGroups', [AvailableController::class, 'index']);
        Route::get('/getFiles', [ContentController::class, 'getFiles']);
        Route::post('/newPass', [UserController::class, 'newPassword']);
    });
    //admin
    Route::middleware(["role:admin"])->group(function () {
        Route::post("/user", [UserController::class, "store"]);
        Route::put("/user", [UserController::class, "update"]);
        Route::delete("/user/{id}", [UserController::class, "delete"]);
        Route::get("/group", [GroupController::class, "index"]);
        Route::post("/group", [GroupController::class, "store"]);
        Route::put("/group", [GroupController::class, "update"]);
        Route::delete("/group/{group}", [GroupController::class, "delete"]);
        Route::post("/about", [AboutController::class, "store"]);
        Route::get("/about", [AboutController::class, "index"]);
        Route::post("/authUser", [UserController::class, "authadmin"]);
        Route::get('/checkImageResource', [ContentController::class, 'checkImageResource']);
    });
});
