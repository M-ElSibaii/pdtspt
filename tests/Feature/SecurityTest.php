<?php

use App\Models\comments;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Tests\Support\PdtFixtures as F;

/*
 * Regression tests for the holes found in the site audit: comment endpoints open to
 * anonymous visitors, commenters' account data in JSON, an open redirect in the language
 * switch, unchecked profile-photo uploads, and missing baseline headers.
 */

beforeEach(function () {
    Mail::fake();
    $this->tpl = F::pdt([1]);
    $this->propertyId = $this->tpl['ctx'][0]->Id ?? DB::table('properties')->where('pdtID', $this->tpl['pdt']->Id)->value('Id');
    $this->author = F::user();
    $this->comment = comments::forceCreate(['body' => 'first', 'properties_Id' => $this->propertyId, 'users_id' => $this->author->id]);
});

test('anonymous visitors cannot delete or read comments', function () {
    $this->delete('/deletefeedback', ['comment_id' => $this->comment->id])->assertRedirect(route('login'));
    $this->post('/comments/' . $this->propertyId)->assertRedirect(route('login'));
    expect(comments::find($this->comment->id))->not->toBeNull();
});

test('only the author or an admin can delete a comment', function () {
    $this->actingAs(F::user())->deleteJson('/deletefeedback', ['comment_id' => $this->comment->id])->assertJsonPath('status', 404);
    expect(comments::find($this->comment->id))->not->toBeNull();

    $this->actingAs($this->author)->deleteJson('/deletefeedback', ['comment_id' => $this->comment->id])->assertJsonPath('status', 200);
    expect(comments::find($this->comment->id))->toBeNull();

    $other = comments::forceCreate(['body' => 'second', 'properties_Id' => $this->propertyId, 'users_id' => $this->author->id]);
    $this->actingAs(F::admin())->deleteJson('/deletefeedback', ['comment_id' => $other->id])->assertJsonPath('status', 200);
    expect(comments::find($other->id))->toBeNull();
});

test('comment lists never expose the commenters e-mail', function () {
    $json = $this->actingAs(F::user())->postJson('/comments/' . $this->propertyId)->assertOk()->json();

    expect($json['comments'][0]['user'])->toHaveKeys(['id', 'name'])
        ->and($json['comments'][0]['user'])->not->toHaveKey('email')
        ->and(json_encode($json))->not->toContain($this->author->email);
});

test('posting a comment validates the property and returns no account data', function () {
    $user = F::user();
    $this->actingAs($user)->postJson(route('pdtssurveystore'), ['properties_Id' => 999999, 'body' => 'x'])->assertJsonPath('status', 400);

    $json = $this->actingAs($user)->postJson(route('pdtssurveystore'), ['properties_Id' => $this->propertyId, 'body' => '<img src=x onerror=alert(1)>'])
        ->assertJsonPath('status', 200)->json();
    expect(json_encode($json))->not->toContain($user->email)->not->toContain($this->author->email);
});

test('survey answers are only stored for real class properties', function () {
    $user = F::user();
    $this->actingAs($user)->post(route('saveAnswers'), ['pdtName' => 'T', $this->propertyId => 'yes', '999999' => 'no', 'isAdmin' => '1'])
        ->assertRedirect();

    expect(DB::table('answers')->where('users_id', $user->id)->pluck('properties_id')->map(fn ($v) => (int) $v)->all())
        ->toBe([(int) $this->propertyId]);
});

test('the survey page escapes comments when it renders them', function () {
    $this->actingAs(F::user())->get(route('pdtssurvey', ['pdtID' => $this->tpl['pdt']->Id]))->assertOk()
        ->assertSee('function esc(s)', false)
        ->assertDontSee("asset(' + comment.user.photo", false);
});

test('the language switch only returns into this site', function () {
    $home = rtrim(url('/'), '/');
    $this->get('/language/en?redirect=' . urlencode($home . '/dashboard?lang=pt'))->assertRedirect($home . '/dashboard');

    foreach ([$home . '.evil.example/x', 'https://evil.example/', '//evil.example/', 'http://user@evil.example/'] as $to) {
        $target = $this->get('/language/en?redirect=' . urlencode($to))->headers->get('Location');
        expect(parse_url($target, PHP_URL_HOST))->toBe(parse_url($home, PHP_URL_HOST));
    }
});

test('profile photo upload accepts images only and never keeps the uploaded name', function () {
    $user = F::user();

    $this->actingAs($user)->post(route('profile.updatePhoto'), [
        'photo' => UploadedFile::fake()->createWithContent('shell.php', '<?php echo 1;'),
    ])->assertSessionHasErrors('photo');
    expect($user->fresh()->photo)->toBeNull();

    $this->actingAs($user)->post(route('profile.updatePhoto'), ['photo' => UploadedFile::fake()->image('me.jpg', 300, 300)])
        ->assertRedirect(route('profile.edit'));
    $photo = $user->fresh()->photo;
    expect($photo)->toStartWith('img/users/' . $user->id . '_')->toEndWith('.png');
    expect(File::exists(public_path($photo)))->toBeTrue();
    File::delete(public_path($photo));
});

test('pages carry baseline security headers', function () {
    $this->get('/')->assertHeader('X-Frame-Options', 'SAMEORIGIN')
        ->assertHeader('X-Content-Type-Options', 'nosniff');
});

test('the database dump is not in the public web root', function () {
    expect(File::exists(public_path('pdtspt.sql')))->toBeFalse();
});
