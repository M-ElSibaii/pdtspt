<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * ISO 23387 UnitType. code is the canonical dictionary symbol (e.g. "mm", "m²").
 * Physics-bearing columns (scale/base/coefficient/offset) are null for now. Stable
 * GUID primary key; referenceURI anchors to QUDT once sourced.
 */
class Unit extends Model
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
            return \App\Services\UriService::build(\App\Services\UriService::UNIT, $this);
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected $table = 'units';
    protected $primaryKey = 'guid';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;
    protected $guarded = [];

    public function physicalQuantity()
    {
        return $this->belongsTo(PhysicalQuantity::class, 'physical_quantity_guid', 'guid');
    }
}
