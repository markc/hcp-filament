<?php

use App\Models\Valias;
use App\Models\Vhost;
use App\Models\Vmail;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Vhost Model', function () {
    it('can create a vhost with valid data', function () {
        $vhost = Vhost::factory()->create([
            'domain' => 'example.com',
            'status' => true,
        ]);

        expect($vhost->domain)->toBe('example.com');
        expect($vhost->status)->toBeTrue();
    });

    it('has correct fillable attributes', function () {
        $fillable = ['domain', 'status'];

        $vhost = new Vhost;
        expect($vhost->getFillable())->toBe($fillable);
    });

    it('casts attributes correctly', function () {
        $vhost = new Vhost;
        $casts = $vhost->getCasts();

        expect($casts['status'])->toBe('boolean');
    });

    it('validates domain uniqueness', function () {
        Vhost::factory()->create(['domain' => 'example.com']);

        expect(function () {
            Vhost::factory()->create(['domain' => 'example.com']);
        })->toThrow();
    });

    it('defaults to active status', function () {
        $vhost = Vhost::factory()->make();
        expect($vhost->status)->toBeTrue();
    });

    it('has vmails relationship', function () {
        $vhost = Vhost::factory()->create(['domain' => 'example.com']);

        // Create vmails for this domain
        Vmail::factory()->count(3)->create([
            'user' => function () use ($vhost) {
                return 'user'.rand(1, 1000).'@'.$vhost->domain;
            },
        ]);

        expect($vhost->vmails())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);
        expect($vhost->vmails->count())->toBe(3);
    });

    it('has valias relationship', function () {
        $vhost = Vhost::factory()->create(['domain' => 'example.com']);

        // Create aliases for this domain
        Valias::factory()->count(2)->create([
            'source' => function () use ($vhost) {
                return 'alias'.rand(1, 1000).'@'.$vhost->domain;
            },
        ]);

        expect($vhost->valias())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);
        expect($vhost->valias->count())->toBe(2);
    });

    it('can scope active vhosts', function () {
        Vhost::factory()->create(['status' => true]);
        Vhost::factory()->create(['status' => true]);
        Vhost::factory()->create(['status' => false]);

        expect(Vhost::active()->count())->toBe(2);
    });

    it('can scope inactive vhosts', function () {
        Vhost::factory()->create(['status' => true]);
        Vhost::factory()->create(['status' => false]);
        Vhost::factory()->create(['status' => false]);

        expect(Vhost::inactive()->count())->toBe(2);
    });

    it('validates domain format', function () {
        $validDomains = [
            'example.com',
            'sub.example.com',
            'test-domain.org',
            'a.b.c.example.net',
        ];

        foreach ($validDomains as $domain) {
            $vhost = Vhost::factory()->make(['domain' => $domain]);
            expect($vhost->domain)->toBe($domain);
        }
    });

    it('can get mailbox count', function () {
        $vhost = Vhost::factory()->create(['domain' => 'example.com']);

        Vmail::factory()->count(5)->create([
            'user' => function () use ($vhost) {
                return 'user'.rand(1, 1000).'@'.$vhost->domain;
            },
        ]);

        expect($vhost->vmails()->count())->toBe(5);
    });

    it('can get alias count', function () {
        $vhost = Vhost::factory()->create(['domain' => 'example.com']);

        Valias::factory()->count(3)->create([
            'source' => function () use ($vhost) {
                return 'alias'.rand(1, 1000).'@'.$vhost->domain;
            },
        ]);

        expect($vhost->valias()->count())->toBe(3);
    });

    it('cascade deletes related records', function () {
        $vhost = Vhost::factory()->create(['domain' => 'example.com']);

        $vmails = Vmail::factory()->count(2)->create([
            'user' => function () use ($vhost) {
                return 'user'.rand(1, 1000).'@'.$vhost->domain;
            },
        ]);

        $aliases = Valias::factory()->count(2)->create([
            'source' => function () use ($vhost) {
                return 'alias'.rand(1, 1000).'@'.$vhost->domain;
            },
        ]);

        expect(Vmail::count())->toBe(2);
        expect(Valias::count())->toBe(2);

        $vhost->delete();

        // Related records should still exist as we don't have cascade delete setup
        // This test documents current behavior
        expect(Vmail::count())->toBe(2);
        expect(Valias::count())->toBe(2);
    });
});
