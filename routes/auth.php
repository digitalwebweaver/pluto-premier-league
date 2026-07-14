<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\ProfileController;
use App\Http\Controllers\Auth\SetPasswordController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Auth routes (Phase 1)
|--------------------------------------------------------------------------
| 1B — tabbed login + logout.
| 1D — forgot / reset / first-login set-password / change password.
*/

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store'])->name('login.store');

    // Forgot / reset password (email token).
    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->name('password.email');
    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset');
    Route::post('reset-password', [NewPasswordController::class, 'store'])
        ->name('password.store');
});

// Forced first-login set-password (authenticated on either guard).
Route::middleware('guard:team,lt')->group(function () {
    Route::get('set-password', [SetPasswordController::class, 'create'])->name('password.set');
    Route::post('set-password', [SetPasswordController::class, 'store'])->name('password.set.store');

    // Change password (requires current password).
    Route::get('settings/password', [PasswordController::class, 'edit'])->name('password.edit');
    Route::put('settings/password', [PasswordController::class, 'update'])->name('password.update');

    // Account / profile (name, contact email, notification preference).
    Route::get('account', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('account', [ProfileController::class, 'update'])->name('profile.update');
});

Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
