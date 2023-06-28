<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Model;

class Answer extends Model
{
    use HasFactory, Notifiable;

    protected $primaryKey = 'id';

    protected $fillable = [
        'user_id',
        'survey_pertanyaan_id',
        'answer',
    ];

    public function surveyPertanyaan()
    {
        return $this->belongsTo(SurveyPertanyaan::class, 'survey_pertanyaan_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function extraAnswers()
    {
        return $this->hasMany(ExtraAnswer::class, 'answer_id');
    }
}
