<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class properties extends Model
{
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
        return $this->hasOne(propertiesdatadictionaries::class, 'GUID', 'GUID');
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
