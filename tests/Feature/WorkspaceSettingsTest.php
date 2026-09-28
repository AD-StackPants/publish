<?php

use App\Models\Organization;
use App\Models\User;
use App\Notifications\WorkspaceInvitationNotification;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected when attempting to access workspace settings', function () {
    $response = $this->get('/w/acme/settings');
    $response->assertRedirect(route('login'));
});

test('tenant member can access workspace settings with comprehensive props', function () {
    $org = Organization::query()->create([
        'name' => 'Acme Corporation',
        'slug' => 'acme-corp',
        'timezone' => 'America/New_York',
    ]);
    $user = User::factory()->create(['organization_id' => $org->id]);

    $this->actingAs($user);

    $response = $this->get('/w/acme-corp/settings');
    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('workspace/Settings')
        ->where('tenant_slug', 'acme-corp')
        ->has('members')
        ->has('passwordRules')
    );
});

test('user cannot view workspace settings of another organization', function () {
    $org1 = Organization::query()->create(['name' => 'Org 1', 'slug' => 'org-1', 'timezone' => 'UTC']);
    $org2 = Organization::query()->create(['name' => 'Org 2', 'slug' => 'org-2', 'timezone' => 'UTC']);

    $user = User::factory()->create(['organization_id' => $org1->id]);
    $this->actingAs($user);

    $response = $this->get('/w/org-2/settings');
    $response->assertForbidden();
});

test('superadmin can access any workspace settings', function () {
    $org = Organization::query()->create(['name' => 'Client Org', 'slug' => 'client-org', 'timezone' => 'UTC']);
    $superadmin = User::factory()->superadmin()->create();

    $this->actingAs($superadmin);

    $response = $this->get('/w/client-org/settings');
    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('workspace/Settings')
        ->where('tenant_slug', 'client-org')
    );
});

test('organization member can invite a new team member', function () {
    $org = Organization::query()->create(['name' => 'Team Org', 'slug' => 'team-org', 'timezone' => 'UTC']);
    $user = User::factory()->create([
        'organization_id' => $org->id,
        'role' => 'Admin',
    ]);

    Notification::fake();

    $this->actingAs($user);

    $response = $this->postJson("/api/v1/organizations/{$org->id}/members", [
        'name' => 'Jane Collaborator',
        'email' => 'jane@example.com',
        'role' => 'Editor',
    ], ['X-Organization-Id' => $org->id]);

    $response->assertCreated()
        ->assertJsonPath('email', 'jane@example.com')
        ->assertJsonPath('role', 'Editor');

    $this->assertDatabaseHas('users', [
        'organization_id' => $org->id,
        'email' => 'jane@example.com',
        'role' => 'Editor',
    ]);

    $invitedUser = User::query()->where('email', 'jane@example.com')->firstOrFail();
    Notification::assertSentTo($invitedUser, WorkspaceInvitationNotification::class);
});

test('cannot invite member if email already belongs to organization', function () {
    $org = Organization::query()->create(['name' => 'Team Org 2', 'slug' => 'team-org-2', 'timezone' => 'UTC']);
    $user = User::factory()->create([
        'organization_id' => $org->id,
        'email' => 'existing@example.com',
        'role' => 'Admin',
    ]);

    $this->actingAs($user);

    $response = $this->postJson("/api/v1/organizations/{$org->id}/members", [
        'name' => 'Existing Duplicate',
        'email' => 'existing@example.com',
        'role' => 'Member',
    ], ['X-Organization-Id' => $org->id]);

    $response->assertStatus(422);
});

test('organization member can remove another member but not themselves', function () {
    $org = Organization::query()->create(['name' => 'Team Org 3', 'slug' => 'team-org-3', 'timezone' => 'UTC']);
    $admin = User::factory()->create([
        'organization_id' => $org->id,
        'role' => 'Admin',
    ]);
    $member = User::factory()->create([
        'organization_id' => $org->id,
        'role' => 'Contributor',
    ]);

    $this->actingAs($admin);

    // Cannot remove oneself
    $selfResponse = $this->deleteJson(
        "/api/v1/organizations/{$org->id}/members/{$admin->id}",
        [],
        ['X-Organization-Id' => $org->id]
    );
    $selfResponse->assertStatus(422);

    // Can remove other member
    $removeResponse = $this->deleteJson(
        "/api/v1/organizations/{$org->id}/members/{$member->id}",
        [],
        ['X-Organization-Id' => $org->id]
    );
    $removeResponse->assertOk();

    $this->assertDatabaseMissing('users', [
        'id' => $member->id,
        'organization_id' => $org->id,
    ]);
});
