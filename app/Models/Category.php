<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory, Notifiable;

    protected $primaryKey = 'id';

    protected $fillable = [
        'name',
        'status'
    ];

    protected $hidden = ['created_at', 'updated_at'];

    // public function questions()
    // {
    //     return $this->hasMany(Question::class, 'category_id');
    // }
}
