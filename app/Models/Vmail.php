<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class Vmail extends Model
{
    use HasFactory;

    protected $table = 'vmails';

    protected $fillable = [
        'user',
        'password',
        'quota',
        'home',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
        'quota' => 'integer',
    ];

    protected $hidden = [
        'password',
    ];

    public function getDomainAttribute(): string
    {
        return explode('@', $this->user)[1] ?? '';
    }

    public function setPasswordAttribute($value): void
    {
        if ($value) {
            $this->attributes['password'] = Hash::make($value);
        }
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeForDomain($query, $domain)
    {
        return $query->where('user', 'like', '%@'.$domain);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($vmail) {
            if (! $vmail->home) {
                $parts = explode('@', $vmail->user);
                if (count($parts) === 2) {
                    $username = $parts[0];
                    $domain = $parts[1];
                    $vmail->home = "/home/u/{$domain}/{$username}";
                }
            }
        });
    }
}
