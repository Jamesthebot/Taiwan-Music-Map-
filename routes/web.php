<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Models\Competition; 

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/map', function () {
    $competitions = Competition::all();
    return view('map', compact('competitions'));
})->name('map');

Route::get('/saved', function () {
    $competitions = Competition::where('saved', true)->get();
    return view('saved', compact('competitions'));
})->name('saved');

Route::post('/competitions/{competition}/save', function (Competition $competition) {
    $competition->update(['saved' => !$competition->saved]);
    return response()->json(['saved' => $competition->saved]);
})->name('competitions.save');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
