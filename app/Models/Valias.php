<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Valias extends Model
{
    use HasFactory;

    protected $table = 'valias';

    protected $fillable = [
        'source',
        'target',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function vhost(): BelongsTo
    {
        return $this->belongsTo(Vhost::class, 'domain', function ($query) {
            return $query->whereRaw('domain = SUBSTRING_INDEX(source, "@", -1) OR domain = SUBSTRING(source, 2)');
        });
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('active', false);
    }

    public function scopeRegular(Builder $query): Builder
    {
        return $query->where('source', 'not like', '@%');
    }

    public function scopeForDomain(Builder $query, string $domain): Builder
    {
        return $query->where(function ($q) use ($domain) {
            $q->whereRaw('SUBSTRING_INDEX(source, "@", -1) = ?', [$domain])
                ->orWhere('source', '@'.$domain);
        });
    }

    public function scopeCatchall(Builder $query): Builder
    {
        return $query->where('source', 'like', '@%');
    }

    public function getDomainAttribute(): string
    {
        if (str_starts_with($this->source, '@')) {
            return substr($this->source, 1);
        }

        return substr(strrchr($this->source, '@'), 1);
    }

    public function getLocalPartAttribute(): ?string
    {
        if (str_starts_with($this->source, '@')) {
            return null; // Catchall
        }

        return substr($this->source, 0, strrpos($this->source, '@'));
    }

    public function getTargetsAttribute(): array
    {
        return array_map('trim', explode(',', $this->target));
    }

    public function getTargetsCountAttribute(): int
    {
        return count($this->targets);
    }

    public function setTargetsAttribute(array $targets): void
    {
        $this->attributes['target'] = implode(',', array_filter($targets));
    }

    public function getIsCatchallAttribute(): bool
    {
        return str_starts_with($this->source, '@');
    }

    public static function validateAlias(string $source, string $target, ?int $excludeId = null): array
    {
        $errors = [];

        // Validate source email format or catchall
        if (! str_starts_with($source, '@') && ! filter_var($source, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Source must be a valid email address or catchall (@domain.com)';
        }

        // Validate targets
        $targets = array_map('trim', explode(',', $target));
        foreach ($targets as $t) {
            if (! filter_var($t, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Target '{$t}' is not a valid email address";
            }
        }

        // Check for source conflicts
        $domain = str_starts_with($source, '@') ? substr($source, 1) : substr(strrchr($source, '@'), 1);

        // Check if domain exists
        if (! Vhost::where('domain', $domain)->exists()) {
            $errors[] = "Domain '{$domain}' does not exist";
        }

        // Check for mailbox conflicts
        if (Vmail::where('user', $source)->exists()) {
            $errors[] = "'{$source}' already exists as a mailbox";
        }

        // Check for alias conflicts
        $existingQuery = static::where('source', $source);
        if ($excludeId) {
            $existingQuery->where('id', '!=', $excludeId);
        }
        if ($existingQuery->exists()) {
            $errors[] = "'{$source}' already exists as an alias";
        }

        // Check source != target
        foreach ($targets as $t) {
            if ($t === $source) {
                $errors[] = 'Source and target cannot be the same';
            }
        }

        return $errors;
    }

    public static function rules(?int $excludeId = null): array
    {
        return [
            'source' => ['required', 'string', 'max:191'],
            'target' => ['required', 'string', 'max:191'],
            'active' => ['boolean'],
        ];
    }
}
