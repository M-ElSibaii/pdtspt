<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class referencedocuments extends Model
{
    use HasFactory;
    public function groupofproperties()
    {
        return $this->belongsTo(groupofproperties::class);
    }
    public function properties()
    {
        return $this->belongsTo(properties::class);
    }
    public function productdatatemplates()
    {
        return $this->belongsTo(productdatatemplates::class);
    }
}
