<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class loins extends Model
{
    use HasFactory;

    protected $fillable = [
        'userId',
        'projectId',
        'projectName',
        'objectName',
        'actorProviding',
        'actorRequesting',
        'pdtName',
        'ifcClass',
        'ifcClassName',
        'ifcClassDescription',
        'ifcClassPredefinedType',
        'materialName',
        'milestone',
        'purpose',
        'detail',
        'dimension',
        'location',
        'appearance',
        'parametricBehaviour',
        'documentation',
        'properties',
        'classificationSystem',
        'classificationTable',
        'classificationCode',
    ];

    public function project()
    {
        return $this->belongsTo(Projects::class);
    }
}
