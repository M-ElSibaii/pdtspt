<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Actors extends Model
{
    use HasFactory;
    protected $fillable = ['projectId', 'actor'];

    public function project()
    {
        return $this->belongsTo(Projects::class, 'projectId');
    }
}
