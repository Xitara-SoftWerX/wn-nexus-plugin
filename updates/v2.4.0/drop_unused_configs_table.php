<?php

namespace Xitara\Nexus\Updates;

use Schema;
use Winter\Storm\Database\Updates\Migration;

class DropUnusedConfigsTable extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('xitara_nexus_configs');
    }

    public function down(): void
    {
        // The table had no model, settings contract, or payload columns and is
        // intentionally not recreated during rollback.
    }
}
