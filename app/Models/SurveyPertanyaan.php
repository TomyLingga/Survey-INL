<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Model;

class SurveyPertanyaan extends Model
{
    use HasFactory, Notifiable;

    protected $primaryKey = 'id';

    protected $fillable = [
        'survey_id',
        'order',
        'value',
        'status'
    ];

    protected $hidden = ['created_at', 'updated_at'];

    public function survey()
    {
        return $this->belongsTo(Survey::class, 'survey_id');
    }

    public function questions()
    {
        return $this->belongsToMany(Question::class, 'sp-pertanyaans', 'survey_pertanyaan_id', 'question_id');
    }

    public function Answers()
    {
        return $this->hasMany(Answer::class, 'survey_pertanyaan_id');
    }
}
