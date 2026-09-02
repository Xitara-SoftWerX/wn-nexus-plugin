<?php

namespace Xitara\Nexus\Updates;

use Schema;
use Winter\Storm\Database\Updates\Migration;

class AddNexusDeletionRequestedAtToBackendUsers extends Migration
{
    public function up(): void
    {
        if (
            !Schema::hasTable('backend_users') ||
            Schema::hasColumn('backend_users', 'nexus_deletion_requested_at')
        ) {
            return;
        }

        Schema::table('backend_users', function ($table): void {
            $table->timestamp('nexus_deletion_requested_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        if (
            !Schema::hasTable('backend_users') ||
            !Schema::hasColumn('backend_users', 'nexus_deletion_requested_at')
        ) {
            return;
        }

        Schema::table('backend_users', function ($table): void {
            $table->dropColumn('nexus_deletion_requested_at');
        });
    }
}
