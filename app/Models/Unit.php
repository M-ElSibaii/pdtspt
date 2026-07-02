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
