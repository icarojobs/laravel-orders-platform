<?php

use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;

it('issues a token with write abilities to operators', function () {
    $user = User::factory()->create();

    $response = $this->postJson(route('api.v1.tokens.store'), [
        'email' => $user->email,
        'password' => 'password',
        'device_name' => 'pest',
    ]);

    $response->assertCreated()
        ->assertJsonPath('abilities', ['orders:read', 'orders:write']);

    expect($response->json('token'))->toBeString();
});

it('issues read only tokens to viewers', function () {
    $user = User::factory()->viewer()->create();

    $this->postJson(route('api.v1.tokens.store'), [
        'email' => $user->email,
        'password' => 'password',
        'device_name' => 'pest',
    ])->assertCreated()->assertJsonPath('abilities', ['orders:read']);
});

it('rejects wrong credentials', function () {
    $user = User::factory()->create();

    $this->postJson(route('api.v1.tokens.store'), [
        'email' => $user->email,
        'password' => 'wrong-password',
        'device_name' => 'pest',
    ])->assertUnprocessable()->assertJsonValidationErrors('email');
});

it('validates the payload', function () {
    $this->postJson(route('api.v1.tokens.store'))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email', 'password', 'device_name']);
});

it('returns the authenticated user and revokes the current token', function () {
    $user = User::factory()->admin()->create();
    $token = $user->createToken('pest')->plainTextToken;

    $this->withToken($token)->getJson(route('api.v1.me'))
        ->assertOk()
        ->assertJson(['email' => $user->email, 'role' => 'admin']);

    $this->withToken($token)->deleteJson(route('api.v1.tokens.destroy'))->assertNoContent();

    expect(PersonalAccessToken::count())->toBe(0);
});

it('blocks anonymous requests', function () {
    $this->getJson(route('api.v1.me'))->assertUnauthorized();
});
