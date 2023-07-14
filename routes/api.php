<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Api\MediaController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\PostController;



/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
//Auth
Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::group([
    'middleware' => 'api',
    'prefix' => 'auth'

], function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::get('/user-profile', [AuthController::class, 'userProfile']);
    Route::post('/change-pass', [AuthController::class, 'changePassWord']);
});

//Media
Route::group([
    'middleware' => 'auth:api',
    'prefix' => 'media'
], function () {
    Route::get('/', [MediaController::class, 'index'])->name('media.index');
    Route::post('/', [MediaController::class, 'store'])->name('media.store');
    Route::get('/{media}', [MediaController::class, 'show'])->name('media.show');
    Route::post('/{media}', [MediaController::class, 'update'])->name('media.update');
    Route::delete('/{media}', [MediaController::class, 'destroy'])->name('media.destroy');
});

//Category
Route::group([
    'middleware' => ['jwt.verify', 'auth:api'],
    'prefix' => 'category'
], function () {
    Route::get('/', [CategoryController::class, 'index'])->name('category.index');
    Route::post('/', [CategoryController::class, 'store'])->name('category.store');
    Route::get('/{category}', [CategoryController::class, 'show'])->name('category.show');
    Route::post('/{category}', [CategoryController::class, 'update'])->name('category.update');
    Route::delete('/{category}', [CategoryController::class, 'destroy'])->name('category.destroy');
    Route::put('/forcedelete', [CategoryController::class, 'forceDelete'])->name('category.forceDelete');
    Route::put('/restore', [CategoryController::class, 'restore'])->name('category.restore');
});

//Post
Route::group([
    'middleware' => ['jwt.verify', 'auth:api'],
    'prefix' => 'post'
], function () {
    Route::get('/', [PostController::class, 'index'])->name('post.index');
    Route::post('/', [PostController::class, 'store'])->name('post.store');
    Route::get('/{post}', [PostController::class, 'show'])->name('post.show');
    Route::post('/{post}', [PostController::class, 'update'])->name('post.update');
    Route::delete('/{post}', [PostController::class, 'destroy'])->name('post.destroy');
    Route::put('/forcedelete', [PostController::class, 'forceDelete'])->name('post.forceDelete');
    Route::put('/restore', [PostController::class, 'restore'])->name('post.restore');
});
