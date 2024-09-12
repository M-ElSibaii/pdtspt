<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class loins extends Model
{
    use HasFactory;

    protected $fillable = [
        'userId',
        'projectName',
        'objectName',
        'name',
        'actorProviding',
        'actorRequesting',
        'pdtName',
        'ifcElement',
        'projectPhase',
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
}
