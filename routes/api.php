<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\Auth\LoginController;
use App\Http\Controllers\API\Articles\ArticlesController;
use App\Http\Controllers\API\Personalize\PersonalizeController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('auth')->namespace('API\Auth')->group(function () {
    Route::post('login', [LoginController::class, 'login']);
    Route::post('register', [LoginController::class, 'register']);
    Route::post('logout', [LoginController::class, 'logout'])->middleware('auth');
});

Route::middleware('auth')->group(function () {
    // Protected routes
    Route::prefix('articles')->namespace('API\Articles')->group(function () {
        Route::get('/', [ArticlesController::class, 'index']);
        Route::post('/', [ArticlesController::class, 'filter']);
    });

    Route::prefix('personalize')->namespace('API\Personalize')->group(function () {
        Route::post('/', [PersonalizeController::class, 'update']);
    });

    // test routes
    Route::get('test', function () {
        return response()->json([
            'success' => true,
            'message' => 'You are authorized',
        ]);
    });
});
