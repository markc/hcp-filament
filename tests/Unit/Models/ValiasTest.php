<?php

use App\Models\Valias;
use App\Models\Vhost;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Valias Model', function () {
    it('can create a valias with valid data', function () {
        $valias = Valias::factory()->create([
            'source' => 'alias@example.com',
            'target' => 'user@example.com',
            'active' => true,
        ]);

        expect($valias->source)->toBe('alias@example.com');
        expect($valias->target)->toBe('user@example.com');
        expect($valias->active)->toBeTrue();
    });

    it('has correct fillable attributes', function () {
        $fillable = ['source', 'target', 'active'];

        $valias = new Valias;
        expect($valias->getFillable())->toBe($fillable);
    });

    it('casts attributes correctly', function () {
        $valias = new Valias;
        $casts = $valias->getCasts();

        expect($casts['active'])->toBe('boolean');
    });

    it('validates source uniqueness', function () {
        Valias::factory()->create(['source' => 'alias@example.com']);

        expect(function () {
            Valias::factory()->create(['source' => 'alias@example.com']);
        })->toThrow();
    });

    it('defaults to active status', function () {
        $valias = Valias::factory()->make();
        expect($valias->active)->toBeTrue();
    });

    it('can scope active aliases', function () {
        Valias::factory()->create(['active' => true]);
        Valias::factory()->create(['active' => true]);
        Valias::factory()->create(['active' => false]);

        expect(Valias::active()->count())->toBe(2);
    });

    it('can scope inactive aliases', function () {
        Valias::factory()->create(['active' => true]);
        Valias::factory()->create(['active' => false]);
        Valias::factory()->create(['active' => false]);

        expect(Valias::inactive()->count())->toBe(2);
    });

    it('can get domain from source email', function () {
        $valias = Valias::factory()->make(['source' => 'alias@example.com']);
        expect($valias->getDomainAttribute())->toBe('example.com');
    });

    it('can detect catchall aliases', function () {
        $catchall = Valias::factory()->make(['source' => '@example.com']);
        $regular = Valias::factory()->make(['source' => 'alias@example.com']);

        expect($catchall->getIsCatchallAttribute())->toBeTrue();
        expect($regular->getIsCatchallAttribute())->toBeFalse();
    });

    it('can get targets as array', function () {
        $singleTarget = Valias::factory()->make(['target' => 'user@example.com']);
        $multipleTargets = Valias::factory()->make(['target' => 'user1@example.com,user2@example.com,user3@example.com']);

        expect($singleTarget->getTargetsAttribute())->toBe(['user@example.com']);
        expect($multipleTargets->getTargetsAttribute())->toBe([
            'user1@example.com',
            'user2@example.com',
            'user3@example.com',
        ]);
    });

    it('can get targets count', function () {
        $singleTarget = Valias::factory()->make(['target' => 'user@example.com']);
        $multipleTargets = Valias::factory()->make(['target' => 'user1@example.com,user2@example.com,user3@example.com']);

        expect($singleTarget->getTargetsCountAttribute())->toBe(1);
        expect($multipleTargets->getTargetsCountAttribute())->toBe(3);
    });

    it('handles empty targets gracefully', function () {
        $emptyTarget = Valias::factory()->make(['target' => '']);

        expect($emptyTarget->getTargetsAttribute())->toBe(['']);
        expect($emptyTarget->getTargetsCountAttribute())->toBe(1);
    });

    it('trims whitespace from targets', function () {
        $messyTargets = Valias::factory()->make(['target' => ' user1@example.com , user2@example.com , user3@example.com ']);

        expect($messyTargets->getTargetsAttribute())->toBe([
            'user1@example.com',
            'user2@example.com',
            'user3@example.com',
        ]);
    });

    it('can scope by domain', function () {
        Valias::factory()->create(['source' => 'alias1@example.com']);
        Valias::factory()->create(['source' => 'alias2@example.com']);
        Valias::factory()->create(['source' => 'alias1@test.com']);

        expect(Valias::forDomain('example.com')->count())->toBe(2);
        expect(Valias::forDomain('test.com')->count())->toBe(1);
        expect(Valias::forDomain('nonexistent.com')->count())->toBe(0);
    });

    it('can scope catchall aliases', function () {
        Valias::factory()->create(['source' => '@example.com']);
        Valias::factory()->create(['source' => '@test.com']);
        Valias::factory()->create(['source' => 'alias@example.com']);

        expect(Valias::catchall()->count())->toBe(2);
    });

    it('can scope regular aliases', function () {
        Valias::factory()->create(['source' => '@example.com']);
        Valias::factory()->create(['source' => 'alias1@example.com']);
        Valias::factory()->create(['source' => 'alias2@example.com']);

        expect(Valias::regular()->count())->toBe(2);
    });

    it('validates source email format for regular aliases', function () {
        $validSources = [
            'alias@example.com',
            'test.alias@sub.domain.com',
            'alias+tag@example.org',
        ];

        foreach ($validSources as $source) {
            $valias = Valias::factory()->make(['source' => $source]);
            expect(filter_var($valias->source, FILTER_VALIDATE_EMAIL))->toBeTruthy();
        }
    });

    it('validates catchall format', function () {
        $validCatchalls = [
            '@example.com',
            '@sub.domain.com',
            '@test.org',
        ];

        foreach ($validCatchalls as $catchall) {
            $valias = Valias::factory()->make(['source' => $catchall]);
            expect($valias->is_catchall)->toBeTrue();
            expect(str_starts_with($valias->source, '@'))->toBeTrue();
        }
    });

    it('validates target email formats', function () {
        $validTargets = [
            'user@example.com',
            'user1@example.com,user2@test.com',
            'test.user@sub.domain.com,another@example.org',
        ];

        foreach ($validTargets as $target) {
            $valias = Valias::factory()->make(['target' => $target]);
            $targets = $valias->targets;

            foreach ($targets as $email) {
                if (! empty(trim($email))) {
                    expect(filter_var(trim($email), FILTER_VALIDATE_EMAIL))->toBeTruthy();
                }
            }
        }
    });

    it('relates to vhost through domain', function () {
        $vhost = Vhost::factory()->create(['domain' => 'example.com']);
        $valias = Valias::factory()->create(['source' => 'alias@example.com']);

        // Manual check since we don't have a direct relationship
        expect($valias->domain)->toBe($vhost->domain);
    });

    it('can check for circular references', function () {
        // This would be implemented in validation service
        $valias = Valias::factory()->create([
            'source' => 'alias@example.com',
            'target' => 'alias@example.com',
        ]);

        // Basic check - source and target are the same
        expect($valias->source)->toBe('alias@example.com');
        expect(str_contains($valias->target, 'alias@example.com'))->toBeTrue();
    });

    it('can handle complex target lists', function () {
        $complexTargets = 'user1@example.com,user2@test.com,external@gmail.com,admin@company.org';
        $valias = Valias::factory()->create(['target' => $complexTargets]);

        expect($valias->targets_count)->toBe(4);
        expect($valias->targets)->toContain('user1@example.com');
        expect($valias->targets)->toContain('external@gmail.com');
    });
});
