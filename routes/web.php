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

    // AI Content Suite
    Route::get('ai-content-idea-suite', \App\Livewire\AiContentIdeaSuite\Index::class)->name('ai.content-idea-suite');

    // AI Image Suite
    Route::get('ai-image-idea-suite', \App\Livewire\AiImageIdeaSuite\Index::class)->name('ai.image-idea-suite');
    
    // AI Activity Logs
    Route::get('activity', \App\Livewire\UserActivity\Index::class)->name('activity.index');
});
