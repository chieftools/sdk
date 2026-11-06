<?php

use Illuminate\Support\Facades\Route;
use ChiefTools\SDK\Exceptions\Handler;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Debug\ExceptionHandler;

beforeEach(function () {
    $this->app->singleton(ExceptionHandler::class, Handler::class);

    Route::get('/protected', static fn () => throw new AuthenticationException);
});

it('asks Livewire to refresh the page when a component update is unauthenticated', function () {
    $this->withHeaders(['X-Livewire' => '1', 'Accept' => '*/*'])
        ->get('/protected')
        ->assertStatus(419)
        ->assertJson(['message' => 'Unauthenticated.']);
});

it('redirects an unauthenticated page request to the login page', function () {
    $this->get('/protected')
        ->assertRedirect(route('login'));
});
