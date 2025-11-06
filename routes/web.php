<?php

use App\Http\Controllers\Auth\EmailLoginController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
})->name('home');

// Email-only authentication routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [EmailLoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [EmailLoginController::class, 'login'])->name('login.attempt');
});

Route::post('/logout', function () {
    auth()->logout();
    return redirect('/');
})->name('logout');

// Protected routes
Route::middleware(['auth'])->group(function () {
    Route::view('dashboard', 'dashboard')
        ->name('dashboard');

    Route::redirect('settings', 'settings/profile');
    Volt::route('settings/profile', 'settings.profile')->name('profile.edit');
    Volt::route('settings/appearance', 'settings.appearance')->name('appearance.edit');
    Volt::route('settings/api-keys', 'settings.api-keys')->name('settings.api-keys');
    Volt::route('ai-content-idea-suite', 'ai-content-idea-suite')->name('ai.content-idea-suite');
    
    // AI Activity Logs
    Route::get('activity', \App\Livewire\UserActivity\Index::class)->name('activity.index');
});
