<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class productdatatemplates extends Model
{
    use HasFactory;
    public function properties()
    {
        return $this->hasMany(properties::class);
    }
    public function groupofproperties()
    {
        return $this->hasMany(groupofproperties::class);
    }
    public function referencedocuments()
    {
        return $this->hasOne(referencedocuments::class, 'referenceDocumentGUID', 'GUID');
    }
}
