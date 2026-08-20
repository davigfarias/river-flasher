<?php

use App\Http\Middleware\EnsureAccessTokenIsValid;
use Illuminate\Support\Facades\Route;

Route::livewire('/', 'pages::auth-code')->name('home');

Route::middleware(EnsureAccessTokenIsValid::class)->group(function () {
    Route::livewire('/dashboard', 'pages::dashboard')->name('dashboard');
    Route::livewire('/decks', 'pages::decks')->name('decks');
    Route::livewire('/decks/{deck}', 'pages::deck-show')->name('decks.show');
    Route::livewire('/study/{deck?}', 'pages::study')->name('study');
    Route::livewire('/flashcards/create', 'pages::flashcards-create')->name('flashcards.create');
});
