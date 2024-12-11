<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Classification extends Model
{
    use HasFactory;

    protected $table = 'classification'; // Table name

    protected $fillable = [
        'classificationSystem',
        'projectId',
    ];
}