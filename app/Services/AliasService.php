<?php

namespace App\Services;

use App\Models\Valias;
use App\Models\Vhost;
use App\Models\Vmail;
use Illuminate\Support\Facades\Log;

class AliasService
{
    public function createAlias(string $source, string $target, bool $active = true): array
    {
        try {
            // Validate the alias
            $errors = $this->validateAlias($source, $target);
            if (! empty($errors)) {
                return ['success' => false, 'message' => implode(', ', $errors)];
            }

            // Process multiple sources if provided
            $sources = $this->parseSources($source);
            $targets = $this->parseTargets($target);
            $targetString = implode(',', $targets);

            $created = [];
            foreach ($sources as $src) {
                // Get domain information
                $domain = $this->extractDomain($src);

                // Create the alias
                $alias = Valias::create([
                    'source' => $src,
                    'target' => $targetString,
                    'active' => $active,
                ]);

                $created[] = $alias;
                Log::info("Created alias: {$src} -> {$targetString}");
            }

            return [
                'success' => true,
                'message' => 'Alias(es) created successfully',
                'aliases' => $created,
            ];

        } catch (\Exception $e) {
            Log::error("Error creating alias {$source} -> {$target}: ".$e->getMessage());

            return ['success' => false, 'message' => 'An error occurred while creating the alias'];
        }
    }

    public function updateAlias(int $id, string $source, string $target, bool $active = true): array
    {
        try {
            $alias = Valias::find($id);
            if (! $alias) {
                return ['success' => false, 'message' => 'Alias not found'];
            }

            // Validate the alias (excluding current ID)
            $errors = $this->validateAlias($source, $target, $id);
            if (! empty($errors)) {
                return ['success' => false, 'message' => implode(', ', $errors)];
            }

            // Parse targets
            $targets = $this->parseTargets($target);
            $targetString = implode(',', $targets);

            // Update the alias
            $alias->update([
                'source' => $source,
                'target' => $targetString,
                'active' => $active,
            ]);

            Log::info("Updated alias ID {$id}: {$source} -> {$targetString}");

            return ['success' => true, 'message' => 'Alias updated successfully'];

        } catch (\Exception $e) {
            Log::error("Error updating alias ID {$id}: ".$e->getMessage());

            return ['success' => false, 'message' => 'An error occurred while updating the alias'];
        }
    }

    public function validateAlias(string $source, string $target, ?int $excludeId = null): array
    {
        $errors = [];

        // Validate source format
        $sources = $this->parseSources($source);
        foreach ($sources as $src) {
            if (! $this->isValidSource($src)) {
                $errors[] = "Invalid source format: {$src}";
            }
        }

        // Validate targets
        $targets = $this->parseTargets($target);
        if (empty($targets)) {
            $errors[] = 'At least one target is required';
        }

        foreach ($targets as $tgt) {
            if (! filter_var($tgt, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Invalid target email: {$tgt}";
            }
        }

        // Check each source for conflicts
        foreach ($sources as $src) {
            $sourceErrors = $this->checkSourceConflicts($src, $targets, $excludeId);
            $errors = array_merge($errors, $sourceErrors);
        }

        return array_unique($errors);
    }

    public function checkCollisions(string $source): array
    {
        $collisions = [];

        // Check if source exists as a mailbox
        if (Vmail::where('user', $source)->exists()) {
            $collisions[] = "'{$source}' already exists as a mailbox";
        }

        // Check if source exists as an alias
        if (Valias::where('source', $source)->exists()) {
            $collisions[] = "'{$source}' already exists as an alias";
        }

        // Check catchall conflicts
        $domain = $this->extractDomain($source);
        if ($domain && Valias::where('source', '@'.$domain)->exists()) {
            $collisions[] = "Catchall alias exists for domain {$domain}";
        }

        return $collisions;
    }

    public function handleCatchall(string $domain): bool
    {
        return Valias::where('source', '@'.$domain)->exists();
    }

    public function processMultipleTargets(array $targets): array
    {
        $processed = [];

        foreach ($targets as $target) {
            $target = trim($target);
            if (! empty($target) && filter_var($target, FILTER_VALIDATE_EMAIL)) {
                $processed[] = $target;
            }
        }

        return array_unique($processed);
    }

    public function getAliasesForDomain(string $domain): \Illuminate\Database\Eloquent\Collection
    {
        return Valias::where(function ($query) use ($domain) {
            $query->whereRaw('SUBSTRING_INDEX(source, "@", -1) = ?', [$domain])
                ->orWhere('source', '@'.$domain);
        })->get();
    }

    private function parseSources(string $source): array
    {
        return array_filter(
            array_map('trim', preg_split('/[,;\s\n]+/', $source)),
            fn ($s) => ! empty($s)
        );
    }

    private function parseTargets(string $target): array
    {
        return array_filter(
            array_map('trim', preg_split('/[,;\s\n]+/', $target)),
            fn ($t) => ! empty($t)
        );
    }

    private function isValidSource(string $source): bool
    {
        // Catchall format (@domain.com)
        if (str_starts_with($source, '@')) {
            $domain = substr($source, 1);

            return $this->isValidDomain($domain);
        }

        // Regular email format
        return filter_var($source, FILTER_VALIDATE_EMAIL) !== false;
    }

    private function isValidDomain(string $domain): bool
    {
        return filter_var($domain, FILTER_VALIDATE_DOMAIN) !== false;
    }

    private function extractDomain(string $source): string
    {
        if (str_starts_with($source, '@')) {
            return substr($source, 1);
        }

        $atPos = strrpos($source, '@');

        return $atPos !== false ? substr($source, $atPos + 1) : '';
    }

    private function checkSourceConflicts(string $source, array $targets, ?int $excludeId = null): array
    {
        $errors = [];
        $domain = $this->extractDomain($source);

        // Check if domain exists
        if ($domain && ! Vhost::where('domain', $domain)->exists()) {
            $errors[] = "Domain '{$domain}' does not exist";
        }

        // Check for mailbox conflicts (unless catchall)
        if (! str_starts_with($source, '@') && Vmail::where('user', $source)->exists()) {
            $errors[] = "'{$source}' already exists as a mailbox";
        }

        // Check for alias conflicts
        $existingQuery = Valias::where('source', $source);
        if ($excludeId) {
            $existingQuery->where('id', '!=', $excludeId);
        }
        if ($existingQuery->exists()) {
            $errors[] = "'{$source}' already exists as an alias";
        }

        // Check source != target (unless catchall)
        if (! str_starts_with($source, '@')) {
            foreach ($targets as $target) {
                if ($target === $source) {
                    $errors[] = 'Source and target cannot be the same';
                    break;
                }
            }
        }

        return $errors;
    }

    public function deleteAlias(int $id): array
    {
        try {
            $alias = Valias::find($id);
            if (! $alias) {
                return ['success' => false, 'message' => 'Alias not found'];
            }

            $source = $alias->source;
            $alias->delete();

            Log::info("Deleted alias: {$source}");

            return ['success' => true, 'message' => 'Alias deleted successfully'];
        } catch (\Exception $e) {
            Log::error("Error deleting alias {$id}: ".$e->getMessage());

            return ['success' => false, 'message' => 'An error occurred while deleting the alias'];
        }
    }

    public function detectCircularReferences(string $source, array $visited = [], int $depth = 0): bool
    {
        if ($depth > 10 || in_array($source, $visited)) {
            return true; // Circular reference detected
        }

        $visited[] = $source;

        $alias = Valias::where('source', $source)->first();
        if (! $alias) {
            return false; // No more aliases in chain
        }

        $targets = $alias->targets;
        foreach ($targets as $target) {
            if ($this->detectCircularReferences($target, $visited, $depth + 1)) {
                return true;
            }
        }

        return false;
    }

    public function getAliasStatistics(): array
    {
        return [
            'total' => Valias::count(),
            'active' => Valias::active()->count(),
            'inactive' => Valias::inactive()->count(),
            'catchall' => Valias::catchall()->count(),
            'regular' => Valias::regular()->count(),
        ];
    }

    public function bulkActivateAliases(array $aliasIds): array
    {
        try {
            $count = Valias::whereIn('id', $aliasIds)->update(['active' => true]);

            return ['success' => true, 'count' => $count];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Error activating aliases'];
        }
    }

    public function bulkDeactivateAliases(array $aliasIds): array
    {
        try {
            $count = Valias::whereIn('id', $aliasIds)->update(['active' => false]);

            return ['success' => true, 'count' => $count];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Error deactivating aliases'];
        }
    }

    public function getAliasDepth(string $source, int $maxDepth = 20): int
    {
        $depth = 0;
        $current = $source;
        $visited = [];

        while ($depth < $maxDepth) {
            if (in_array($current, $visited)) {
                return $maxDepth; // Circular reference
            }

            $visited[] = $current;
            $alias = Valias::where('source', $current)->first();

            if (! $alias) {
                break; // End of chain
            }

            $targets = $alias->targets;
            if (empty($targets)) {
                break;
            }

            // Follow the first target for depth calculation
            $current = $targets[0];
            $depth++;
        }

        return $depth;
    }
}
