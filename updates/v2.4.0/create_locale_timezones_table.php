<?php

namespace Xitara\Nexus\Updates;

use DB;
use Schema;
use Winter\Storm\Database\Schema\Blueprint;
use Winter\Storm\Database\Updates\Migration;

class CreateLocaleTimezonesTable extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('xitara_nexus_locale_timezones')) {
            Schema::create('xitara_nexus_locale_timezones', function (Blueprint $table): void {
                $table->string('locale_code')->primary();
                $table->string('timezone');
            });
        }

        $this->importLegacyTranslateValues();
    }

    public function down(): void
    {
        Schema::dropIfExists('xitara_nexus_locale_timezones');
    }

    /**
     * Preserve values from the unreleased conditional migration if it was
     * applied in a development installation before the storage decision.
     */
    private function importLegacyTranslateValues(): void
    {
        if (
            !Schema::hasTable('winter_translate_locales') ||
            !Schema::hasColumn('winter_translate_locales', 'nexus_timezone')
        ) {
            return;
        }

        DB::table('winter_translate_locales')
            ->whereNotNull('nexus_timezone')
            ->where('nexus_timezone', '<>', '')
            ->orderBy('id')
            ->get(['code', 'nexus_timezone'])
            ->each(function ($locale): void {
                DB::table('xitara_nexus_locale_timezones')->updateOrInsert(
                    ['locale_code' => $locale->code],
                    ['timezone' => $locale->nexus_timezone],
                );
            });

        Schema::table('winter_translate_locales', function (Blueprint $table): void {
            $table->dropColumn('nexus_timezone');
        });
    }
}
