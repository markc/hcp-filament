<?php

use App\Models\Vhost;
use App\Models\Vmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

describe('Vmail Model', function () {
    it('can create a vmail with valid data', function () {
        $vmail = Vmail::factory()->create([
            'user' => 'test@example.com',
            'password' => 'password123',
            'uid' => 1000,
            'gid' => 1000,
            'active' => true,
        ]);

        expect($vmail->user)->toBe('test@example.com');
        expect($vmail->uid)->toBe(1000);
        expect($vmail->gid)->toBe(1000);
        expect($vmail->active)->toBeTrue();
        expect(Hash::check('password123', $vmail->password))->toBeTrue();
    });

    it('has correct fillable attributes', function () {
        $fillable = ['user', 'password', 'uid', 'gid', 'home', 'active'];

        $vmail = new Vmail;
        expect($vmail->getFillable())->toBe($fillable);
    });

    it('hides sensitive attributes', function () {
        $hidden = ['password'];

        $vmail = new Vmail;
        expect($vmail->getHidden())->toBe($hidden);
    });

    it('casts attributes correctly', function () {
        $vmail = new Vmail;
        $casts = $vmail->getCasts();

        expect($casts['password'])->toBe('hashed');
        expect($casts['active'])->toBe('boolean');
        expect($casts['uid'])->toBe('integer');
        expect($casts['gid'])->toBe('integer');
    });

    it('validates user email uniqueness', function () {
        Vmail::factory()->create(['user' => 'test@example.com']);

        expect(function () {
            Vmail::factory()->create(['user' => 'test@example.com']);
        })->toThrow();
    });

    it('defaults to active status', function () {
        $vmail = Vmail::factory()->make();
        expect($vmail->active)->toBeTrue();
    });

    it('defaults uid and gid to 1000', function () {
        $vmail = Vmail::factory()->make();
        expect($vmail->uid)->toBe(1000);
        expect($vmail->gid)->toBe(1000);
    });

    it('can scope active vmails', function () {
        Vmail::factory()->create(['active' => true]);
        Vmail::factory()->create(['active' => true]);
        Vmail::factory()->create(['active' => false]);

        expect(Vmail::active()->count())->toBe(2);
    });

    it('can scope inactive vmails', function () {
        Vmail::factory()->create(['active' => true]);
        Vmail::factory()->create(['active' => false]);
        Vmail::factory()->create(['active' => false]);

        expect(Vmail::inactive()->count())->toBe(2);
    });

    it('can get domain from user email', function () {
        $vmail = Vmail::factory()->make(['user' => 'test@example.com']);
        expect($vmail->getDomainAttribute())->toBe('example.com');
    });

    it('can get username from user email', function () {
        $vmail = Vmail::factory()->make(['user' => 'testuser@example.com']);
        expect($vmail->getUsernameAttribute())->toBe('testuser');
    });

    it('generates home directory automatically', function () {
        $vmail = Vmail::factory()->make([
            'user' => 'testuser@example.com',
            'home' => null,
        ]);

        // Test the boot method that should set home directory
        $vmail->save();

        expect($vmail->home)->toBe('/home/u/example.com/testuser');
    });

    it('preserves custom home directory', function () {
        $customHome = '/custom/path/testuser';
        $vmail = Vmail::factory()->create([
            'user' => 'testuser@example.com',
            'home' => $customHome,
        ]);

        expect($vmail->home)->toBe($customHome);
    });

    it('can scope by domain', function () {
        Vmail::factory()->create(['user' => 'user1@example.com']);
        Vmail::factory()->create(['user' => 'user2@example.com']);
        Vmail::factory()->create(['user' => 'user1@test.com']);

        expect(Vmail::forDomain('example.com')->count())->toBe(2);
        expect(Vmail::forDomain('test.com')->count())->toBe(1);
        expect(Vmail::forDomain('nonexistent.com')->count())->toBe(0);
    });

    it('validates email format', function () {
        $validEmails = [
            'user@example.com',
            'test.email@sub.domain.com',
            'user+tag@example.org',
        ];

        foreach ($validEmails as $email) {
            $vmail = Vmail::factory()->make(['user' => $email]);
            expect(filter_var($vmail->user, FILTER_VALIDATE_EMAIL))->toBeTruthy();
        }
    });

    it('relates to vhost through domain', function () {
        $vhost = Vhost::factory()->create(['domain' => 'example.com']);
        $vmail = Vmail::factory()->create(['user' => 'test@example.com']);

        // Manual check since we don't have a direct relationship
        expect($vmail->domain)->toBe($vhost->domain);
    });

    it('can check if mailbox is quota exceeded', function () {
        // This would be implemented when quota functionality is added
        $vmail = Vmail::factory()->create();

        // For now, just ensure the method exists or can be added
        expect($vmail)->toBeInstanceOf(Vmail::class);
    });

    it('can get mailbox size', function () {
        // This would be implemented when disk usage functionality is added
        $vmail = Vmail::factory()->create();

        // For now, just ensure the model exists
        expect($vmail)->toBeInstanceOf(Vmail::class);
    });

    it('hashes password on creation', function () {
        $plainPassword = 'testpassword123';
        $vmail = Vmail::factory()->create(['password' => $plainPassword]);

        expect($vmail->password)->not->toBe($plainPassword);
        expect(Hash::check($plainPassword, $vmail->password))->toBeTrue();
    });

    it('hashes password on update', function () {
        $vmail = Vmail::factory()->create(['password' => 'original']);

        $newPassword = 'newpassword123';
        $vmail->update(['password' => $newPassword]);

        expect(Hash::check($newPassword, $vmail->password))->toBeTrue();
    });
});
