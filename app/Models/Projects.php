<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Projects extends Model
{
    use HasFactory;
    protected $fillable = ['projectName', 'description', 'userId',];

    public function milestones()
    {
        return $this->hasMany(Milestones::class);
    }

    public function actors()
    {
        return $this->hasMany(Actors::class);
    }

    public function purposes()
    {
        return $this->hasMany(Purposes::class);
    }

    public function objects()
    {
        return $this->hasMany(Objects::class);
    }
    public function loins()
    {
        return $this->hasMany(Loins::class, 'projectId');
    }
}
