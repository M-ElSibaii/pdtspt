<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * ISO 23387 DimensionType — 7 SI exponents (ISO 80000 order). Physics is filled in a
 * later pass; rows may exist with null exponents. Stable GUID primary key.
 */
class Dimension extends Model
{
    protected $table = 'dimensions';
    protected $primaryKey = 'guid';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;
    protected $guarded = [];

    public function physicalQuantities()
    {
        return $this->hasMany(PhysicalQuantity::class, 'dimension_guid', 'guid');
    }
}
