<?php

use App\Models\User;
use Tests\Support\PdtFixtures as F;

/*
 * Process (a), user roles: POST /admin/update sets isAdmin / subscribe for the users on
 * the admin page. An admin may not change their own admin flag (no self-demotion that
 * could leave the site without an admin, no confusion over who granted what), and a
 * partial or stale form must not end in a 500.
 */

beforeEach(function () {
    $this->admin = F::admin();
    $this->other = F::user();
});

test('an admin cannot change their own admin flag; the rest of the form still applies', function () {
    $this->actingAs($this->admin)->from('/admin')->post('/admin/update', [
        'isAdmin' => [$this->admin->id => 0, $this->other->id => 1],
        'subscribe' => [$this->admin->id => 1, $this->other->id => 1],
    ])->assertRedirect('/admin')->assertSessionHasErrors('isAdmin');

    expect((int) $this->admin->fresh()->isAdmin)->toBe(1)
        ->and((int) $this->other->fresh()->isAdmin)->toBe(1)
        ->and((int) $this->admin->fresh()->subscribe)->toBe(1)
        ->and((int) $this->other->fresh()->subscribe)->toBe(1);
});

test('the admin page form, which always sends the admin\'s own unchanged flag, saves without an error', function () {
    $this->actingAs($this->admin)->from('/admin')->post('/admin/update', [
        'isAdmin' => [$this->admin->id => 1, $this->other->id => 1],
        'subscribe' => [$this->admin->id => 0, $this->other->id => 0],
    ])->assertRedirect('/admin')->assertSessionHasNoErrors()->assertSessionHas('success');

    expect((int) $this->other->fresh()->isAdmin)->toBe(1);
});

test('an admin can remove another admin', function () {
    $second = F::admin();
    $this->actingAs($this->admin)->from('/admin')->post('/admin/update', ['isAdmin' => [$second->id => 0]])
        ->assertRedirect('/admin')->assertSessionHasNoErrors();
    expect((int) $second->fresh()->isAdmin)->toBe(0);
});

test('a partial form or an unknown user id does not answer 500', function () {
    $this->actingAs($this->admin)->from('/admin')->post('/admin/update', [])->assertRedirect('/admin');
    $this->actingAs($this->admin)->from('/admin')->post('/admin/update', ['isAdmin' => [999999 => 1], 'subscribe' => [999999 => 1]])
        ->assertRedirect('/admin');
    expect(User::find(999999))->toBeNull();
});

test('a non-admin cannot grant themselves admin', function () {
    $this->actingAs($this->other)->post('/admin/update', ['isAdmin' => [$this->other->id => 1]])->assertRedirect('/');
    expect((int) $this->other->fresh()->isAdmin)->toBe(0);
});

test('the refusal is shown on the admin page', function () {
    $this->actingAs($this->admin)->from('/admin')->followingRedirects()
        ->post('/admin/update', ['isAdmin' => [$this->admin->id => 0]])
        ->assertOk()->assertSee('You cannot change your own admin status');
});
