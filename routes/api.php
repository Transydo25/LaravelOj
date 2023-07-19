<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Api\MediaController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\VerifyEmailController;
use App\Models\User;
use App\Models\Post;





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
    Route::get('/', [AuthController::class, 'index']);
    Route::post('/create', [AuthController::class, 'create'])->can('create', User::class);
    Route::post('/update/{user}', [AuthController::class, 'update'])->can('update', 'user');
    Route::delete('/{user}', [AuthController::class, 'destroy'])->middleware('can:delete,user');
});

//Mail
Route::get('/email/verify/{id}/{hash}', [VerifyEmailController::class, 'verifyEmail'])
    ->middleware(['auth', 'signed'])->name('verification.verify');

Route::post('/email/verify/resend', function (Request $request) {
    $request->user()->sendEmailVerificationNotification();
    return back()->with('message', 'Verification link sent!');
})->middleware(['auth:api', 'throttle:6,1'])->name('verification.send');

//Media
Route::group([
    'middleware' => 'auth:api',
    'prefix' => 'media'
], function () {
    Route::get('/', [MediaController::class, 'index']);
    Route::post('/', [MediaController::class, 'store']);
    Route::get('/{media}', [MediaController::class, 'show']);
    Route::post('/{media}', [MediaController::class, 'update']);
    Route::delete('/{media}', [MediaController::class, 'destroy']);
});

//Category
Route::group([
    'middleware' => ['jwt.verify', 'auth:api'],
    'prefix' => 'category'
], function () {
    Route::get('/', [CategoryController::class, 'index']);
    Route::post('/', [CategoryController::class, 'store']);
    Route::get('/{category}', [CategoryController::class, 'show']);
    Route::post('/{category}', [CategoryController::class, 'update']);
    Route::put('/', [CategoryController::class, 'deleteCategory']);
    Route::put('/restore', [CategoryController::class, 'restore']);
});

//Post
Route::group([
    'middleware' => ['jwt.verify', 'auth:api'],
    'prefix' => 'post'
], function () {
    Route::get('/', [PostController::class, 'index']);
    Route::post('/', [PostController::class, 'store']);
    Route::get('/{post}', [PostController::class, 'show']);
    Route::post('/{post}', [PostController::class, 'update'])->can('update', 'post');
    Route::put('/', [PostController::class, 'deletePost'])->can('delete', Post::class);
    Route::put('/restore', [PostController::class, 'restore'])->can('restore', Post::class);
});
