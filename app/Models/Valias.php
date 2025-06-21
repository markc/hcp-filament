<?php

namespace App\Models;

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
        $domain = $this->getDomainAttribute();

        return $this->belongsTo(Vhost::class, 'domain', 'domain')
            ->where('domain', $domain);
    }

    public function getDomainAttribute(): string
    {
        if (str_starts_with($this->source, '@')) {
            return substr($this->source, 1);
        }

        return explode('@', $this->source)[1] ?? '';
    }

    public function getIsCatchallAttribute(): bool
    {
        return str_starts_with($this->source, '@');
    }

    public function getTargetArrayAttribute(): array
    {
        return array_map('trim', explode(',', $this->target));
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeCatchall($query)
    {
        return $query->where('source', 'like', '@%');
    }

    public function scopeForDomain($query, $domain)
    {
        return $query->where(function ($q) use ($domain) {
            $q->where('source', 'like', '%@'.$domain)
                ->orWhere('source', '@'.$domain);
        });
    }
}
