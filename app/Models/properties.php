<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class properties extends Model
{
    protected $table = 'properties';
    protected $primaryKey = 'Id';
    public $timestamps = false;
    protected $fillable = [
        'descriptionEn',
        'descriptionPt',

        // Add other fields as needed
    ];
    use HasFactory;
    public function groupofproperties()
    {
        return $this->belongsTo(GroupOfProperties::class, 'gopID', 'Id');
    }
    public function productdatatemplates()
    {
        return $this->belongsTo(ProductDataTemplates::class, 'pdtID', 'Id');
    }
    public function propertiesdatadictionaries()
    {
        return $this->belongsTo(PropertiesDataDictionaries::class, 'GUID', 'GUID');
    }
    public function referencedocuments()
    {
        return $this->hasOne(ReferenceDocuments::class, 'referenceDocumentGUID', 'GUID');
    }
    public function comments()
    {
        return $this->hasMany(Comments::class)->whereNull('parent_id');
    }
    public function answers()
    {
        return $this->hasOne(Answers::class);
    }
}
