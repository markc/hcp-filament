<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vhost extends Model
{
    use HasFactory;

    protected $table = 'vhosts';

    protected $fillable = [
        'domain',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    // Get vmails for this domain (computed relationship)
    public function getVmailsAttribute()
    {
        return Vmail::where('user', 'like', '%@'.$this->domain)->get();
    }

    // Get aliases for this domain (computed relationship)
    public function getValiasAttribute()
    {
        return Valias::where(function ($query) {
            $query->where('source', 'like', '%@'.$this->domain)
                ->orWhere('source', '@'.$this->domain);
        })->get();
    }

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('status', false);
    }
}
