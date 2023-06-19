<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    use HasFactory, Notifiable;

    protected $primaryKey = 'id';

    protected $fillable = [
        'category_id',
        'question',
        'type',
        'require',
        'status'
    ];

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function option()
    {
        return $this->hasMany(Option::class, 'question_id');
    }

    public function extraAnswer()
    {
        return $this->hasMany(ExtraAnswer::class, 'question_id');
    }
}
