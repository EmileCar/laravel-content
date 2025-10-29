<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('page_contents', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name')->unique(); // used to look up pages
            $table->string('display_name'); // human-readable-title
            $table->json('value')->nullable(); // stored JSON content
            $table->string('type')->default('page'); // optional: page, fragment, block, landing
            $table->string('locale', 10)->nullable(); // en, en_US etc. optional for i18n
            $table->unsignedBigInteger('version')->default(1);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['type', 'locale']);
            $table->index('name');
        });
    }

    public function down()
    {
        Schema::dropIfExists('page_contents');
    }
};