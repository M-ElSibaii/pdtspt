<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class referencedocuments extends Model
{
    /** Expose the canonical identifier on every serialized record. */
    protected $appends = ['uri'];

    /**
     * The record's canonical PDTs.pt identifier, appended to every serialization so
     * each record the API returns carries the URI it is known by. Built by UriService;
     * null when the record has no usable name (reported by `php artisan uri:check`).
     */
    public function getUriAttribute(): ?string
    {
        try {
            return \App\Services\UriService::build(\App\Services\UriService::DOCUMENT, $this);
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected $fillable = [
        'GUID',
        'rdName',
        'description',
        'status',
        'title',
    ];
    use HasFactory;
    public function groupofproperties()
    {
        return $this->belongsTo(GroupOfProperties::class);
    }
    public function properties()
    {
        return $this->belongsTo(Properties::class);
    }
    public function productdatatemplates()
    {
        return $this->belongsTo(ProductDataTemplates::class);
    }
}
