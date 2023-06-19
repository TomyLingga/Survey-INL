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

    public function question()
    {
        return $this->belongsTo(Question::class, 'question_id');
    }
}
