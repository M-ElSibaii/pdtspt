<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class referencedocuments extends Model
{
    protected $table = 'referencedocument';
    protected $primaryKey = 'GUID';
    public $timestamps = false;
    use HasFactory;
    public function groupofproperties()
    {
        return $this->belongsTo(GroupOfProperties::class);
    }
    public function properties()
    {
        return $this->belongsTo(Properties::class);
    }
    public function productdatatemplates()
    {
        return $this->belongsTo(ProductDataTemplates::class);
    }
}
