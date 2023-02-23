<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Likes extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id', 'comment_id', 'reply_id'
    ];
    public function comments()
    {
        return $this->belongsTo(comments::class, 'comments_Id', 'id');
    }
}
