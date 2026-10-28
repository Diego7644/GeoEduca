<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn () => response()->json(['app' => 'GEOEDUCA API', 'docs' => '/api/health']));
