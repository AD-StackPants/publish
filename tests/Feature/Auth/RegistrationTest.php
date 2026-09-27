<?php

use App\Models\User;
use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::registration());
});

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('new users can register and auto-assign tenant organization', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));

    $user = User::query()->where('email', 'john@example.com')->firstOrFail();
    expect($user->organization_id)->not->toBeNull();
    expect($user->organization)->not->toBeNull();
    expect($user->organization->name)->toBe('John Doe Workspace');
    expect($user->organization->subscription)->not->toBeNull();
    expect($user->organization->subscription->status)->toBe('active');
});

test('new users can register with custom organization details', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Jane Smith',
        'email' => 'jane@example.com',
        'organization_name' => 'Acme Global Ventures',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));

    $user = User::query()->where('email', 'jane@example.com')->firstOrFail();
    expect($user->organization_id)->not->toBeNull();
    expect($user->organization->name)->toBe('Acme Global Ventures');
    expect($user->organization->slug)->toBe('acme-global-ventures');
    expect($user->organization->subscription)->not->toBeNull();
});
