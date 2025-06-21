<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\Rule;

class Vmail extends Model
{
    use HasFactory;

    protected $fillable = [
        'user',
        'password',
        'uid',
        'gid',
        'home',
        'active',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'password' => 'hashed',
        'active' => 'boolean',
        'uid' => 'integer',
        'gid' => 'integer',
    ];

    public function vhost(): BelongsTo
    {
        return $this->belongsTo(Vhost::class, 'domain', function ($query) {
            return $query->whereRaw('domain = SUBSTRING_INDEX(user, "@", -1)');
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

    public function scopeForDomain(Builder $query, string $domain): Builder
    {
        return $query->whereRaw('SUBSTRING_INDEX(user, "@", -1) = ?', [$domain]);
    }

    public function getDomainAttribute(): string
    {
        return substr(strrchr($this->user, '@'), 1);
    }

    public function getUsernameAttribute(): string
    {
        return substr($this->user, 0, strrpos($this->user, '@'));
    }

    public function getLocalPartAttribute(): string
    {
        return $this->username;
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($vmail) {
            if (empty($vmail->home)) {
                $vmail->home = '/home/u/'.$vmail->domain.'/'.$vmail->username;
            }
            if (empty($vmail->uid)) {
                $vmail->uid = 1000;
            }
            if (empty($vmail->gid)) {
                $vmail->gid = 1000;
            }
        });
    }

    public static function rules(): array
    {
        return [
            'user' => ['required', 'email', 'max:191', Rule::unique('vmails')],
            'password' => ['required', 'min:8'],
            'gid' => ['integer', 'min:1'],
            'uid' => ['integer', 'min:1'],
            'active' => ['boolean'],
        ];
    }
}
