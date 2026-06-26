<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * One target property of a PropertyDependency (R-23387-8). Multiple targets per dependency
 * are ordered by position; isPreferred marks the preferred alternative-proxy target.
 */
class PropertyDependencyTarget extends Model
{
    use HasFactory;

    protected $table = 'property_dependency_targets';

    protected $fillable = [
        'dependencyId', 'targetPropertyGuid', 'isPreferred', 'position',
        'targetVersionNumber', 'targetRevisionNumber',
    ];

    protected $casts = [
        'isPreferred'          => 'boolean',
        'position'             => 'integer',
        'targetVersionNumber'  => 'integer',
        'targetRevisionNumber' => 'integer',
    ];

    public function dependency()
    {
        return $this->belongsTo(PropertyDependency::class, 'dependencyId');
    }
}
