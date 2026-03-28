<?php

namespace Xitara\Nexus\Updates;

use Schema;
use Winter\Storm\Database\Updates\Migration;

class CreateCustomMenusTable extends Migration
{
    public function up()
    {
        Schema::create('xitara_nexus_custommenus', function ($table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('name', 20)->nullable();
            $table->string('slug', 30)->nullable();
            $table->text('links')->nullable();
            $table->boolean('is_submenu')->default(0);
            $table->boolean('is_active')->default(1);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('xitara_nexus_custommenus');
    }
}
