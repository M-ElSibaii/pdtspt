<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Milestones extends Model
{
    use HasFactory;
    protected $fillable = ['projectId', 'milestone'];

    public function project()
    {
        return $this->belongsTo(Projects::class, 'projectId');
    }
}
