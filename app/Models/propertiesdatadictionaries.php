<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class propertiesdatadictionaries extends Model
{
    protected $table = 'propertiesdatadictionaries';
    protected $primaryKey = 'Id';
    public $timestamps = false;
    use HasFactory;
    public function properties()
    {
        return $this->hasMany(Properties::class, 'GUID', 'GUID');
    }
}
