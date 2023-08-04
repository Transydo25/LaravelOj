<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\UploadController;
use App\Http\Controllers\Api\ArticleController;
use App\Http\Controllers\Api\RevisionArticleController;
use App\Http\Controllers\Api\TopPageController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\VerifyEmailController;
use App\Models\User;
use App\Models\Post;
use App\Models\Category;
use App\Models\Article;
use App\Models\RevisionArticle;

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

//Mail
Route::get('/email/verify/{id}/{hash}', [VerifyEmailController::class, 'verifyEmail'])
    ->middleware(['auth', 'signed'])->name('verification.verify');

Route::post('/email/verify/resend', function (Request $request) {
    $request->user()->sendEmailVerificationNotification();
    return back()->with('message', 'Verification link sent!');
})->middleware(['auth:api', 'throttle:6,1']);


Route::group([
    'middleware' => ['jwt.verify', 'auth:api'],
], function () {
    //User
    Route::get('/user', [UserController::class, 'index'])->can('viewAny', User::class);
    Route::get('/user/favorite', [UserController::class, 'showFavorite']);
    Route::post('/user/favorite', [UserController::class, 'manageFavorite']);
    Route::post('/user', [UserController::class, 'create'])->can('create', User::class);
    Route::get('/user/{user}', [UserController::class, 'show'])->can('view', 'user');
    Route::post('/user/{user}', [UserController::class, 'update'])->can('update', 'user');
    Route::delete('/user', [UserController::class, 'destroy'])->can('delete', User::class);
    Route::put('/user/restore', [UserController::class, 'restore'])->can('restore', User::class);
    Route::post('/user/approve/{article}', [UserController::class, 'approve'])->can('status', User::class);
    Route::post('/user/revision_article/{revision_article}', [UserController::class, 'approveRevisionArticle'])->can('status', User::class);
    //Upload
    Route::post('/upload', [UploadController::class, 'store']);
    Route::post('/upload/video', [UploadController::class, 'uploadVideo']);
    //Category
    Route::get('/category', [CategoryController::class, 'index']);
    Route::post('/category', [CategoryController::class, 'store'])->can('create', Category::class);
    Route::get('/category/{category}', [CategoryController::class, 'show']);
    Route::post('/category/{category}', [CategoryController::class, 'update'])->can('update', 'category');
    Route::delete('/category', [CategoryController::class, 'destroy'])->can('delete', Category::class);
    Route::put('/category/restore', [CategoryController::class, 'restore'])->can('restore', Category::class);
    //Post
    Route::get('/post', [PostController::class, 'index']);
    Route::post('/post', [PostController::class, 'store'])->can('create', Post::class);
    Route::get('/post/{post}', [PostController::class, 'show']);
    Route::post('/post/{post}', [PostController::class, 'update'])->can('update', 'post');
    Route::post('/post/detail/{post}', [PostController::class, 'updateDetails'])->can('update', 'post');
    Route::delete('/post', [PostController::class, 'destroy'])->can('delete', Post::class);
    Route::put('/post/restore', [PostController::class, 'restore'])->can('restore', Post::class);
    //Article
    Route::get('/article', [ArticleController::class, 'index']);
    Route::post('/article', [ArticleController::class, 'store'])->can('create', Article::class);
    Route::get('/article/{article}', [ArticleController::class, 'show']);
    Route::post('/article/{article}', [ArticleController::class, 'update'])->can('update', 'article');
    Route::post('/article/detail/{article}', [ArticleController::class, 'updateDetails'])->can('update', 'article');
    Route::delete('/article', [ArticleController::class, 'destroy'])->can('delete', Article::class);
    Route::put('/article/restore', [ArticleController::class, 'restore'])->can('restore', Article::class);
    //RevisionArticle
    Route::get('/revision_article', [RevisionArticleController::class, 'index']);
    Route::post('/revision_article/{article}', [RevisionArticleController::class, 'store'])->can('create', RevisionArticle::class);
    Route::get('/revision_article/{revision_article}', [RevisionArticleController::class, 'show']);
    Route::get('/revision_article/list/{article}', [RevisionArticleController::class, 'list']);
    Route::post('/revision_article/update/{revision_article}', [RevisionArticleController::class, 'update'])->can('update', 'revision_article');
    Route::post('/revision_article/detail/{revision_article}', [RevisionArticleController::class, 'updateDetail'])->can('update', RevisionArticle::class);
    //TopPage
    Route::post('/top_page', [TopPageController::class, 'store']);
    Route::get('/top_page/{top_page}', [TopPageController::class, 'show']);
    Route::post('/top_page/{top_page}', [TopPageController::class, 'update'])->can('update', 'top_page');
    Route::post('/top_page/detail/{top_page}', [TopPageController::class, 'updateDetails'])->can('update', 'top_page');
    //Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index']);
});
