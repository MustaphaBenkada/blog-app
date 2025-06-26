<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Mail;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/test-email', function () {
    Mail::raw('This is a test email from Laravel.', function ($message) {
        $message->to('your_email@example.com') // <-- Replace with your email
                ->subject('Test Email');
    });
    return 'Test email sent!';
});
