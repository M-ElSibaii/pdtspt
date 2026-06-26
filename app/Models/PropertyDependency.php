<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A property's typed dependency (EN ISO 23387:2025 R-23387-8, Table 1).
 * Header row; its target properties live in PropertyDependencyTarget.
 */
class PropertyDependency extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'property_dependencies';

    // The five Table-1 sub-kinds.
    public const KIND_ADDITIVE_CONTEXT    = 'additive_context';
    public const KIND_COMBINATIVE_CONTEXT = 'combinative_context';
    public const KIND_REFERENCE_PROXY     = 'reference_proxy';
    public const KIND_ALTERNATIVE_PROXY   = 'alternative_proxy';
    public const KIND_FUNCTION            = 'function';

    public const KINDS = [
        self::KIND_ADDITIVE_CONTEXT,
        self::KIND_COMBINATIVE_CONTEXT,
        self::KIND_REFERENCE_PROXY,
        self::KIND_ALTERNATIVE_PROXY,
        self::KIND_FUNCTION,
    ];

    protected $fillable = [
        'sourcePropertyGuid', 'dependencyKind', 'expression', 'note',
    ];

    public function targets()
    {
        return $this->hasMany(PropertyDependencyTarget::class, 'dependencyId')
            ->orderByRaw('position IS NULL, position');
    }
}
