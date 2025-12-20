<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

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
            // Drop the unique constraint with locale
            $table->dropUnique(['page_id', 'element_id', 'locale']);

            // Remove duplicate entries before recreating old unique constraint
            // Keep only the first record for each page_id + element_id combination
            DB::statement('
                DELETE FROM page_contents
                WHERE id NOT IN (
                    SELECT MIN(id)
                    FROM page_contents
                    GROUP BY page_id, element_id
                )
            ');

            // Recreate the old unique constraint
            $table->unique(['page_id', 'element_id']);

            // Drop locale column
            $table->dropColumn('locale');
        });
    }
};
