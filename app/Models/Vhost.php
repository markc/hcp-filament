<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vhost extends Model
{
    use HasFactory;

    protected $fillable = [
        'domain',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function vmails(): HasMany
    {
        return $this->hasMany(Vmail::class, 'user', function ($query) {
            return $query->whereRaw('SUBSTRING_INDEX(user, "@", -1) = domain');
        });
    }

    public function valias(): HasMany
    {
        return $this->hasMany(Valias::class, 'source', function ($query) {
            return $query->whereRaw('SUBSTRING_INDEX(source, "@", -1) = domain OR source LIKE CONCAT("@", domain)');
        });
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', true);
    }

    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('status', false);
    }

    public function getMailboxCountAttribute(): int
    {
        return $this->vmails()->count();
    }

    public function getAliasCountAttribute(): int
    {
        return $this->valias()->count();
    }
}
