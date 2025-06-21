<?php

use App\Models\Valias;
use App\Models\Vhost;
use App\Models\Vmail;
use App\Services\AliasService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('AliasService', function () {
    beforeEach(function () {
        $this->aliasService = new AliasService;
    });

    it('can validate a simple alias', function () {
        $vhost = Vhost::factory()->create(['domain' => 'example.com']);

        $errors = $this->aliasService->validateAlias('alias@example.com', 'user@example.com');

        expect($errors)->toBe([]);
    });

    it('validates source email format', function () {
        $vhost = Vhost::factory()->create(['domain' => 'example.com']);

        $errors = $this->aliasService->validateAlias('invalid-email', 'user@example.com');

        expect($errors)->toContain('Source must be a valid email address or catchall (@domain.com)');
    });

    it('allows catchall source format', function () {
        $vhost = Vhost::factory()->create(['domain' => 'example.com']);

        $errors = $this->aliasService->validateAlias('@example.com', 'user@example.com');

        expect($errors)->toBe([]);
    });

    it('validates target email formats', function () {
        $vhost = Vhost::factory()->create(['domain' => 'example.com']);

        $errors = $this->aliasService->validateAlias('alias@example.com', 'invalid-email,user@example.com');

        expect($errors)->toContain("Target 'invalid-email' is not a valid email address");
    });

    it('validates domain exists', function () {
        $errors = $this->aliasService->validateAlias('alias@nonexistent.com', 'user@example.com');

        expect($errors)->toContain("Domain 'nonexistent.com' does not exist");
    });

    it('checks for mailbox conflicts', function () {
        $vhost = Vhost::factory()->create(['domain' => 'example.com']);
        Vmail::factory()->create(['user' => 'test@example.com']);

        $errors = $this->aliasService->validateAlias('test@example.com', 'user@example.com');

        expect($errors)->toContain("'test@example.com' already exists as a mailbox");
    });

    it('checks for alias conflicts', function () {
        $vhost = Vhost::factory()->create(['domain' => 'example.com']);
        Valias::factory()->create(['source' => 'existing@example.com']);

        $errors = $this->aliasService->validateAlias('existing@example.com', 'user@example.com');

        expect($errors)->toContain("'existing@example.com' already exists as an alias");
    });

    it('allows updating existing alias', function () {
        $vhost = Vhost::factory()->create(['domain' => 'example.com']);
        $alias = Valias::factory()->create(['source' => 'existing@example.com']);

        $errors = $this->aliasService->validateAlias('existing@example.com', 'newuser@example.com', $alias->id);

        expect($errors)->toBe([]);
    });

    it('prevents source and target being the same', function () {
        $vhost = Vhost::factory()->create(['domain' => 'example.com']);

        $errors = $this->aliasService->validateAlias('alias@example.com', 'alias@example.com,user@example.com');

        expect($errors)->toContain('Source and target cannot be the same');
    });

    it('can create a simple alias', function () {
        $vhost = Vhost::factory()->create(['domain' => 'example.com']);

        $result = $this->aliasService->createAlias('alias@example.com', 'user@example.com');

        expect($result['success'])->toBeTrue();
        expect($result['alias'])->toBeInstanceOf(Valias::class);
        expect($result['alias']->source)->toBe('alias@example.com');
        expect($result['alias']->target)->toBe('user@example.com');
    });

    it('can create a catchall alias', function () {
        $vhost = Vhost::factory()->create(['domain' => 'example.com']);

        $result = $this->aliasService->createAlias('@example.com', 'admin@example.com');

        expect($result['success'])->toBeTrue();
        expect($result['alias']->source)->toBe('@example.com');
        expect($result['alias']->is_catchall)->toBeTrue();
    });

    it('can create alias with multiple targets', function () {
        $vhost = Vhost::factory()->create(['domain' => 'example.com']);

        $result = $this->aliasService->createAlias('alias@example.com', 'user1@example.com,user2@example.com,user3@example.com');

        expect($result['success'])->toBeTrue();
        expect($result['alias']->targets)->toHaveCount(3);
        expect($result['alias']->targets_count)->toBe(3);
    });

    it('returns validation errors when creating invalid alias', function () {
        $result = $this->aliasService->createAlias('alias@nonexistent.com', 'user@example.com');

        expect($result['success'])->toBeFalse();
        expect($result['errors'])->toContain("Domain 'nonexistent.com' does not exist");
    });

    it('can update an existing alias', function () {
        $vhost = Vhost::factory()->create(['domain' => 'example.com']);
        $alias = Valias::factory()->create([
            'source' => 'alias@example.com',
            'target' => 'olduser@example.com',
        ]);

        $result = $this->aliasService->updateAlias($alias->id, 'alias@example.com', 'newuser@example.com');

        expect($result['success'])->toBeTrue();

        $alias->refresh();
        expect($alias->target)->toBe('newuser@example.com');
    });

    it('can delete an alias', function () {
        $alias = Valias::factory()->create();

        $result = $this->aliasService->deleteAlias($alias->id);

        expect($result['success'])->toBeTrue();
        expect(Valias::find($alias->id))->toBeNull();
    });

    it('handles deleting non-existent alias', function () {
        $result = $this->aliasService->deleteAlias(99999);

        expect($result['success'])->toBeFalse();
        expect($result['message'])->toContain('not found');
    });

    it('can detect circular references', function () {
        $vhost = Vhost::factory()->create(['domain' => 'example.com']);

        // Create alias chain: alias1 -> alias2 -> alias3 -> alias1
        $alias1 = Valias::factory()->create(['source' => 'alias1@example.com', 'target' => 'alias2@example.com']);
        $alias2 = Valias::factory()->create(['source' => 'alias2@example.com', 'target' => 'alias3@example.com']);
        $alias3 = Valias::factory()->create(['source' => 'alias3@example.com', 'target' => 'alias1@example.com']);

        $circular = $this->aliasService->detectCircularReferences('alias1@example.com');

        expect($circular)->toBeTrue();
    });

    it('can detect complex circular references', function () {
        $vhost = Vhost::factory()->create(['domain' => 'example.com']);

        // Create more complex chain with multiple targets
        $alias1 = Valias::factory()->create(['source' => 'alias1@example.com', 'target' => 'user@example.com,alias2@example.com']);
        $alias2 = Valias::factory()->create(['source' => 'alias2@example.com', 'target' => 'alias3@example.com']);
        $alias3 = Valias::factory()->create(['source' => 'alias3@example.com', 'target' => 'alias1@example.com,admin@example.com']);

        $circular = $this->aliasService->detectCircularReferences('alias1@example.com');

        expect($circular)->toBeTrue();
    });

    it('returns false for non-circular references', function () {
        $vhost = Vhost::factory()->create(['domain' => 'example.com']);

        // Create simple forward chain
        $alias1 = Valias::factory()->create(['source' => 'alias1@example.com', 'target' => 'alias2@example.com']);
        $alias2 = Valias::factory()->create(['source' => 'alias2@example.com', 'target' => 'user@example.com']);

        $circular = $this->aliasService->detectCircularReferences('alias1@example.com');

        expect($circular)->toBeFalse();
    });

    it('can get aliases for a domain', function () {
        $vhost = Vhost::factory()->create(['domain' => 'example.com']);
        Valias::factory()->count(3)->create(['source' => function () {
            return fake()->userName().'@example.com';
        }]);
        Valias::factory()->create(['source' => 'other@test.com']);

        $aliases = $this->aliasService->getAliasesForDomain('example.com');

        expect($aliases->count())->toBe(3);
    });

    it('can get alias statistics', function () {
        $vhost1 = Vhost::factory()->create(['domain' => 'example.com']);
        $vhost2 = Vhost::factory()->create(['domain' => 'test.com']);

        Valias::factory()->count(3)->create(['source' => function () {
            return fake()->userName().'@example.com';
        }, 'active' => true]);
        Valias::factory()->create(['source' => 'inactive@example.com', 'active' => false]);
        Valias::factory()->create(['source' => '@example.com']); // Catchall
        Valias::factory()->create(['source' => 'alias@test.com']);

        $stats = $this->aliasService->getAliasStatistics();

        expect($stats['total'])->toBe(6);
        expect($stats['active'])->toBe(5);
        expect($stats['inactive'])->toBe(1);
        expect($stats['catchall'])->toBe(1);
        expect($stats['regular'])->toBe(5);
    });

    it('can bulk activate aliases', function () {
        $aliases = Valias::factory()->count(3)->create(['active' => false]);
        $aliasIds = $aliases->pluck('id')->toArray();

        $result = $this->aliasService->bulkActivateAliases($aliasIds);

        expect($result['success'])->toBeTrue();
        expect($result['count'])->toBe(3);

        foreach ($aliases as $alias) {
            $alias->refresh();
            expect($alias->active)->toBeTrue();
        }
    });

    it('can bulk deactivate aliases', function () {
        $aliases = Valias::factory()->count(3)->create(['active' => true]);
        $aliasIds = $aliases->pluck('id')->toArray();

        $result = $this->aliasService->bulkDeactivateAliases($aliasIds);

        expect($result['success'])->toBeTrue();
        expect($result['count'])->toBe(3);

        foreach ($aliases as $alias) {
            $alias->refresh();
            expect($alias->active)->toBeFalse();
        }
    });

    it('can check alias depth', function () {
        $vhost = Vhost::factory()->create(['domain' => 'example.com']);

        // Create a deep chain: alias1 -> alias2 -> alias3 -> alias4 -> user@example.com
        $alias1 = Valias::factory()->create(['source' => 'alias1@example.com', 'target' => 'alias2@example.com']);
        $alias2 = Valias::factory()->create(['source' => 'alias2@example.com', 'target' => 'alias3@example.com']);
        $alias3 = Valias::factory()->create(['source' => 'alias3@example.com', 'target' => 'alias4@example.com']);
        $alias4 = Valias::factory()->create(['source' => 'alias4@example.com', 'target' => 'user@example.com']);

        $depth = $this->aliasService->getAliasDepth('alias1@example.com');

        expect($depth)->toBe(4);
    });

    it('limits alias depth to prevent infinite loops', function () {
        $vhost = Vhost::factory()->create(['domain' => 'example.com']);

        // Create circular reference
        $alias1 = Valias::factory()->create(['source' => 'alias1@example.com', 'target' => 'alias2@example.com']);
        $alias2 = Valias::factory()->create(['source' => 'alias2@example.com', 'target' => 'alias1@example.com']);

        $depth = $this->aliasService->getAliasDepth('alias1@example.com', 10);

        expect($depth)->toBe(10); // Should hit the limit
    });
});
