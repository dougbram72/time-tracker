<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

// Simple login route for demo
Route::get('/login', function () {
    $user = User::where('email', 'test@example.com')->first();
    if ($user) {
        Auth::login($user);
        return redirect('/dashboard')->with('success', 'Logged in successfully!');
    }
    return redirect('/')->with('error', 'Demo user not found. Please run the seeder.');
})->name('login');

// Logout route
Route::post('/logout', function () {
    Auth::logout();
    return redirect('/')->with('success', 'Logged out successfully!');
})->name('logout');

Route::get('/', function () {
    if (Auth::check()) {
        return view('dashboard');
    }
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->name('dashboard')->middleware('auth');
