<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class properties extends Model
{
    protected $fillable = [
        'descriptionEn',
        'descriptionPt',

        // Add other fields as needed
    ];
    use HasFactory;
    public function groupofproperties()
    {
        return $this->belongsTo(groupofproperties::class, 'gopID', 'Id');
    }
    public function productdatatemplates()
    {
        return $this->belongsTo(productdatatemplates::class, 'pdtID', 'Id');
    }
    public function propertiesdatadictionaries()
    {
        return $this->belongsTo(DataDictionary::class, 'GUID', 'GUID');
    }
    public function referencedocuments()
    {
        return $this->hasOne(referencedocuments::class, 'referenceDocumentGUID', 'GUID');
    }
    public function comments()
    {
        return $this->hasMany(comments::class)->whereNull('parent_id');
    }
    public function answers()
    {
        return $this->hasOne(Answers::class);
    }
}
