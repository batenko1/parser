<?php

use App\Http\Controllers\ParserResultController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ParserResultController::class, 'index'])->name('index');

