<?php

use App\Http\Controllers\GmailController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/oauth2callback', [GmailController::class, 'handleOAuth2Callback']); 
Route::get('/send-emails', [GmailController::class, 'sendEmails']); 
