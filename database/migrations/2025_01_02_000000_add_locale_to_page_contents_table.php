<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('page_contents', function (Blueprint $table) {

            $table->string('locale', 10)->default('en')->after('element_id');
            $table->dropUnique(['page_id', 'element_id']);
            $table->unique(['page_id', 'element_id', 'locale']);
        });
    }

    public function down()
    {
        Schema::table('page_contents', function (Blueprint $table) {
            $table->dropUnique(['page_id', 'element_id', 'locale']);
            $table->unique(['page_id', 'element_id']);
            $table->dropColumn('locale');
        });
    }
};
