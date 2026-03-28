<?php

use Winter\Storm\Database\Updates\Migration;
use Winter\Storm\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $table = 'xitara_nexus_custommenus';
        if (Schema::hasTable($table)) {
            if (Schema::hasColumns($table, ['namespace'])) {
                return;
            }

            Schema::table($table, function ($table) {
                $table->string('namespace')->after('name')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $table = 'xitara_nexus_custommenus';
        if (Schema::hasTable($table)) {
            Schema::table($table, function ($table) {
                $table->dropColumnIfExists('namespace');
            });
        }
    }
};
