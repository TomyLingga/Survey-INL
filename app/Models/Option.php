<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Model;

class Option extends Model
{
    use HasFactory, Notifiable;

    protected $primaryKey = 'id';

    protected $fillable = [
        'question_id',
        'value',
        'description',
        'extra_answer',
        'status'
    ];

    protected $hidden = ['created_at', 'updated_at'];

    public function question()
    {
        return $this->belongsTo(Question::class, 'question_id');
    }

    public function extraAnswers()
    {
        return $this->hasMany(ExtraAnswer::class, 'option_id');
    }
}
