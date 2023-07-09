<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
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

    protected $hidden = ['created_at', 'updated_at'];


    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function options()
    {
        return $this->hasMany(Option::class, 'question_id');
    }

    public function surveyPertanyaans()
    {
        return $this->belongsToMany(SurveyPertanyaan::class, 'sp-pertanyaans', 'survey_pertanyaan_id', 'question_id');
    }
}
