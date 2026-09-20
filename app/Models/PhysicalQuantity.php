<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * ISO 23387 QuantityKindType (physical quantity). Carries the English name + language
 * for the ISO 23386 "physical quantity | language" output pairing, and an optional
 * DimensionRef (filled later). Stable GUID primary key.
 */
class PhysicalQuantity extends Model
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
            return \App\Services\UriService::build(\App\Services\UriService::QUANTITY_KIND, $this);
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected $table = 'physical_quantities';
    protected $primaryKey = 'guid';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;
    protected $guarded = [];

    public function dimension()
    {
        return $this->belongsTo(Dimension::class, 'dimension_guid', 'guid');
    }

    public function units()
    {
        return $this->hasMany(Unit::class, 'physical_quantity_guid', 'guid');
    }
}
