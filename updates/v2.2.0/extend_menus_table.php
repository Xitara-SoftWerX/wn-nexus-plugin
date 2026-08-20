<?php

namespace Xitara\Nexus\Updates;

use Schema;
use Winter\Storm\Database\Updates\Migration;

class ExtendMenusTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('xitara_nexus_menus')) {
            return;
        }

        Schema::table('xitara_nexus_menus', function ($table) {
            if (!Schema::hasColumn('xitara_nexus_menus', 'owner')) {
                $table->string('owner', 100)->nullable()->after('code');
            }

            if (!Schema::hasColumn('xitara_nexus_menus', 'main_menu_code')) {
                $table->string('main_menu_code', 100)->nullable()->after('owner');
            }

            if (!Schema::hasColumn('xitara_nexus_menus', 'source_type')) {
                $table->string('source_type', 20)->default('legacy')->after('main_menu_code');
            }

            if (!Schema::hasColumn('xitara_nexus_menus', 'is_enabled')) {
                $table->boolean('is_enabled')->default(true)->after('source_type');
            }

            if (!Schema::hasColumn('xitara_nexus_menus', 'last_seen_at')) {
                $table->timestamp('last_seen_at')->nullable()->after('sort_order');
            }
        });
    }

    public function down()
    {
        if (!Schema::hasTable('xitara_nexus_menus')) {
            return;
        }

        Schema::table('xitara_nexus_menus', function ($table) {
            foreach (['owner', 'main_menu_code', 'source_type', 'is_enabled', 'last_seen_at'] as $column) {
                if (Schema::hasColumn('xitara_nexus_menus', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
}
