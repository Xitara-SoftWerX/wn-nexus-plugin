<?php

namespace Xitara\Nexus\Updates;

use Schema;
use Winter\Storm\Database\Updates\Migration;

class CreateMenusTable extends Migration
{
    public function up()
    {
        Schema::create('xitara_nexus_menus', function ($table) {
            $table->engine = 'InnoDB';
            $table->string('code', 100)->nullable();
            $table->string('name', 100)->nullable();
            $table->integer('sort_order')->nullable();
            $table->unique('code');
        });
    }

    public function down()
    {
        Schema::dropIfExists('xitara_nexus_menus');
    }
}
