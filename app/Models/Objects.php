<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Objects extends Model
{
    use HasFactory;

    protected $fillable = ['projectId', 'object', 'ifcClass'];

    public function project()
    {
        return $this->belongsTo(Projects::class, 'projectId');
    }
}
