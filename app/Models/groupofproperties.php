<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class groupofproperties extends Model
{
    use HasFactory;
    public function properties()
    {
        return $this->hasMany(properties::class);
    }
    public function productdatatemplates()
    {
        return $this->belongsTo(productdatatemplates::class, 'pdtID', 'Id');
    }
    public function referencedocuments()
    {
        return $this->hasOne(referencedocuments::class, 'referenceDocumentGUID', 'GUID');
    }
}
