<?php

use Illuminate\Support\Facades\Route;

Route::view('/admin/{path?}', 'admin')->where('path', '.*');

Route::redirect('/', '/admin');
