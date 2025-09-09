<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LeaseController;
use App\Http\Controllers\Penalites\ApplicationPenaliteController;



Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');



// route affichage du lease
    Route::get('/leases', [LeaseController::class, 'index'])->name('lease.index');

//routes pour le paiement du lease
Route::post('/leases/pay', [LeaseController::class, 'pay'])->name('leases.pay');


//application pernalité
Route::get('/penalites/apply-today', [ApplicationPenaliteController::class, 'appliquerPourAujourdhui'])
     ->name('penalites.applyToday');





});

require __DIR__.'/auth.php';















