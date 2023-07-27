<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\UploadController;
use App\Http\Controllers\Api\ArticleController;
use App\Http\Controllers\VerifyEmailController;
use App\Models\User;
use App\Models\Post;
use App\Models\Category;





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
    Route::post('/update', [AuthController::class, 'update']);
});

Route::group([
    'middleware' => ['jwt.verify', 'auth:api'],
    'prefix' => 'user'
], function () {
    //Favorites
    Route::get('/', [UserController::class, 'index'])->can('viewAny', User::class);
    Route::get('/favorite', [UserController::class, 'showFavorite']);
    Route::post('/favorite', [UserController::class, 'manageFavorite']);
    //User
    Route::post('/', [UserController::class, 'create'])->can('create', User::class);
    Route::get('/{user}', [UserController::class, 'show'])->can('view', 'user');
    Route::post('/{user}', [UserController::class, 'update'])->can('update', 'user');
    Route::put('/', [UserController::class, 'destroy'])->can('delete', 'user');
    Route::put('/restore', [UserController::class, 'restore'])->can('restore', User::class);
    Route::get('/meta', [UserController::class, 'profile']);
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
    'middleware' => ['jwt.verify', 'auth:api'],
    'prefix' => 'upload'
], function () {
    Route::get('/', [UploadController::class, 'index']);
    Route::post('/', [UploadController::class, 'store']);
    Route::get('/{media}', [UploadController::class, 'show']);
    Route::post('/update', [UploadController::class, 'update']);
    Route::delete('/{media}', [UploadController::class, 'destroy']);
});

//Category
Route::group([
    'middleware' => ['jwt.verify', 'auth:api'],
    'prefix' => 'category'
], function () {
    Route::get('/', [CategoryController::class, 'index']);
    Route::post('/', [CategoryController::class, 'store'])->can('create', Category::class);
    Route::get('/{category}', [CategoryController::class, 'show']);
    Route::post('/{category}', [CategoryController::class, 'update'])->can('update', 'category');
    Route::put('/', [CategoryController::class, 'deleteCategory'])->can('delete', Category::class);
    Route::put('/restore', [CategoryController::class, 'restore'])->can('restore', Category::class);
});

//Post
Route::group([
    'middleware' => ['jwt.verify', 'auth:api'],
    'prefix' => 'post'
], function () {
    Route::get('/', [PostController::class, 'index']);
    Route::post('/', [PostController::class, 'store'])->can('create', Post::class);
    Route::get('/{post}', [PostController::class, 'show']);
    Route::post('/{post}', [PostController::class, 'update'])->can('update', 'post');
    Route::post('/detail/{post}', [PostController::class, 'updateDetails'])->can('update', 'post');
    Route::put('/', [PostController::class, 'deletePost'])->can('delete', Post::class);
    Route::put('/restore', [PostController::class, 'restore'])->can('restore', Post::class);
});

//Article
Route::group([
    'middleware' => ['jwt.verify', 'auth:api'],
    'prefix' => 'article'
], function () {
    Route::get('/', [ArticleController::class, 'index']);
    Route::post('/', [ArticleController::class, 'store'])->can('create', Article::class);
    Route::post('/status', [ArticleController::class, 'status'])->can('status', Article::class);
    Route::get('/{article}', [ArticleController::class, 'show']);
    Route::post('/{article}', [ArticleController::class, 'update'])->can('update', 'article');
    Route::post('/detail/{article}', [ArticleController::class, 'updateDetails'])->can('update', 'article');
    Route::put('/', [ArticleController::class, 'deleteArticle'])->can('delete', Article::class);
    Route::put('/restore', [ArticleController::class, 'restore'])->can('restore', Article::class);
});
