<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('page_contents', function (Blueprint $table) {
            $table->id();
            $table->string('page_id');
            $table->string('element_id');
            $table->enum('type', ['text', 'image', 'file']);
            $table->text('value')->nullable();
            $table->timestamps();

            $table->unique(['page_id', 'element_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('page_contents');
    }
};