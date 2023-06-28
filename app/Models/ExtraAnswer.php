<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Model;

class ExtraAnswer extends Model
{
    use HasFactory, Notifiable;

    protected $primaryKey = 'id';

    protected $fillable = [
        'answer_id',
        'option_id',
        'value',
    ];

    protected $hidden = ['created_at', 'updated_at'];

    public function answer()
    {
        return $this->belongsTo(Answer::class, 'answer_id');
    }

    public function option()
    {
        return $this->belongsTo(Option::class, 'option_id');
    }
}
