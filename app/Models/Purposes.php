<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Purposes extends Model
{
    use HasFactory;

    protected $fillable = ['projectId', 'purpose'];

    public function project()
    {
        return $this->belongsTo(Projects::class, 'projectId');
    }
}
