<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSurveyPertanyaanPertanyaansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('survey_pertanyaan-pertanyaans', function (Blueprint $table) {
            $table->foreignId('survey_pertanyaan_id')->constrained('survey_pertanyaans');
            $table->foreignId('question_id')->constrained('questions');
            $table->string('order');
            $table->string('status');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('survey_pertanyaan-_pertanyaans');
    }
}
