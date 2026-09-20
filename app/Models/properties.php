<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class properties extends Model
{
    /**
     * The record's canonical PDTs.pt identifier, appended to every serialization so
     * each record the API returns carries the URI it is known by. Built by UriService;
     * null when the record has no usable name (reported by `php artisan uri:check`).
     */
    public function getUriAttribute(): ?string
    {
        try {
            return \App\Services\UriService::build(\App\Services\UriService::CLASS_PROPERTY, $this);
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected $table = 'properties';
    protected $primaryKey = 'Id';
    public $timestamps = false;
    protected $fillable = [
        'descriptionEn',
        'descriptionPt',
        'referenceDocumentGUID',
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
