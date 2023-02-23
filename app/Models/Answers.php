<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Answers extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id', 'property_id', 'answer'
    ];
    public function user()
    {
        return $this->belongsTo(User::class, 'users_id', 'id');
    }
    public function properties()
    {
        return $this->belongsTo(properties::class, 'properties_Id', 'Id');
    }
}
