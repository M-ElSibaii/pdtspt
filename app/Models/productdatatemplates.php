<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class productdatatemplates extends Model
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
            return \App\Services\UriService::build(\App\Services\UriService::DATA_TEMPLATE, $this);
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected $table = 'productdatatemplates';
    protected $primaryKey = 'Id';
    public $timestamps = false;
    use HasFactory;
    public function properties()
    {
        return $this->hasMany(Properties::class);
    }
    public function groupofproperties()
    {
        return $this->hasMany(GroupOfProperties::class, 'pdtId', 'Id');
    }
    public function referencedocuments()
    {
        return $this->hasOne(ReferenceDocuments::class, 'referenceDocumentGUID', 'GUID');
    }
}
