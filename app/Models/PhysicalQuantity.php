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
