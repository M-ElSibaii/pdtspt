<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class propertiesdatadictionaries extends Model
{
    use HasFactory;
    public function properties()
    {
        return $this->hasMany(Properties::class, 'GUID', 'GUID');
    }
    public function latestVersion()
    {
        // Assuming 'versionNumber' is a column in your data dictionary table
        return $this->where('GUID', $this->GUID)->orderByDesc('versionNumber')->first();
    }
}
