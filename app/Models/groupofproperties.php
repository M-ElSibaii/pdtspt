<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class groupofproperties extends Model
{
    protected $table = 'groupofproperties';
    protected $primaryKey = 'Id';
    public $timestamps = false;
    use HasFactory;

    public function productdatatemplates()
    {
        return $this->belongsTo(ProductDataTemplates::class, 'pdtID', 'Id');
    }
    public function referencedocuments()
    {
        return $this->hasOne(ReferenceDocuments::class, 'referenceDocumentGUID', 'GUID');
    }
    public function properties()
    {
        return $this->hasMany(Properties::class, 'gopId');
    }
}
