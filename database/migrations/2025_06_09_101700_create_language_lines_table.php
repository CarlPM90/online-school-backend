<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomLanguageLinesTable extends Migration
{
    public function up()
    {
        Schema::create('language_lines', function (Blueprint $table) {
            $table->id();
            $table->string('group');
            $table->string('key');
            $table->text('text');
            $table->timestamps();

            $table->index(['group', 'key']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('language_lines');
    }
}