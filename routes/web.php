<?php

use App\Http\Controllers\ParserResultController;
use App\Http\Controllers\SiteController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ParserResultController::class, 'index'])->name('index');

Route::get('sites', [SiteController::class, 'index'])->name('sites');
Route::post('sites', [SiteController::class, 'save'])->name('sites.save');
