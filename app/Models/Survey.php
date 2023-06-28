<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Model;

class Survey extends Model
{
    use HasFactory, Notifiable;

    protected $primaryKey = 'id';

    protected $fillable = [
        'title',
        'desc',
        'from',
        'to',
        'status'
    ];

    public function surveyPertanyaans()
    {
        return $this->hasMany(SurveyPertanyaan::class, 'survey_id');
    }
}
