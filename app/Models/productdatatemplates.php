<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class productdatatemplates extends Model
{
    protected $table = 'productdatatemplates';
    protected $primaryKey = 'Id';
    public $timestamps = false;
    use HasFactory;
    public function properties()
    {
        return $this->hasMany(Properties::class);
    }
    public function groupofproperties()
    {
        return $this->hasMany(GroupOfProperties::class, 'pdtId', 'Id');
    }
    public function referencedocuments()
    {
        return $this->hasOne(ReferenceDocuments::class, 'referenceDocumentGUID', 'GUID');
    }
}
