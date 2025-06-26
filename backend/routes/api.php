<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BlogPostController;
use App\Http\Controllers\Api\CommentController;

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

//==========================================================================
// Auth Routes
//==========================================================================
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/request-otp', [AuthController::class, 'requestOtp']);
Route::post('/login-otp', [AuthController::class, 'loginWithOtp']);

//==========================================================================
// Public Routes
//==========================================================================

// Public Blog Post Routes
Route::get('posts/search', [BlogPostController::class, 'search']);
Route::get('posts', [BlogPostController::class, 'index']);
Route::get('posts/{post}', [BlogPostController::class, 'show']);

// Utility Routes
Route::post('rebuild-search-index', [BlogPostController::class, 'rebuildSearchIndex']);
Route::post('test-published-email', [BlogPostController::class, 'testPublishedEmail']);

//==========================================================================
// Protected Routes (Authentication Required)
//==========================================================================
Route::middleware('auth:api')->group(function () {
    // User's Posts
    Route::get('my-posts', [BlogPostController::class, 'myPosts']);
    Route::post('publish-scheduled', [BlogPostController::class, 'publishScheduled']);
    Route::get('debug-post-status', [BlogPostController::class, 'debugPostStatus']);

    // Blog Post CRUD
    Route::apiResource('posts', BlogPostController::class)->except(['index', 'show']);
    Route::post('posts/upload-image', [BlogPostController::class, 'uploadImage']);

    // Comment Routes
    Route::post('posts/{post}/comments', [CommentController::class, 'store']);
    Route::delete('comments/{comment}', [CommentController::class, 'destroy']);

    // User Profile
    Route::patch('/profile', [AuthController::class, 'updateProfile']);
    Route::get('/profile', [AuthController::class, 'profile']);
});
