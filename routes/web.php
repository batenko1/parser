<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ParserResultController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ParserResultController::class, 'index'])->name('index');

Route::match(['get', 'post'], 'login', [AuthController::class, 'login'])->name('login');

//Route::get('sites', [SiteController::class, 'index'])->name('sites');
//Route::post('sites', [SiteController::class, 'save'])->name('sites.save');

Route::get('/logout', [AuthController::class, 'logout'])->name('logout');
