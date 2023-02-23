<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Mail\ContactMail;

class Contact extends Model
{
    use HasFactory;
    //  protected $guarded = [];
    public $fillable = ['name', 'email', 'phone', 'subject', 'message'];

    //   /**
    //    * Write code on Method
    //    *
    //    * @return response()
    //    */
    //  public static function boot()
    //   {

    // parent::boot();

    //  static::created(function ($item) {

    //      $adminEmail = "pdts.portugal@gmail.com";
    //      ContactMail::to($adminEmail)->send(new ContactMail($item));
    // });
    //}
}
