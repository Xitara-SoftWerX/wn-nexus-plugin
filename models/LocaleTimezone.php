<?php

namespace Xitara\Nexus\Models;

use DateTimeZone;
use Model;
use Winter\Storm\Exception\ValidationException;

class LocaleTimezone extends Model
{
    public $table = 'xitara_nexus_locale_timezones';

    protected $primaryKey = 'locale_code';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = ['locale_code', 'timezone'];

    public static function forLocaleCode(?string $localeCode): ?string
    {
        if (!$localeCode) {
            return null;
        }

        return static::query()->whereKey($localeCode)->value('timezone');
    }

    public static function storeForLocaleCode(?string $localeCode, $timezone): void
    {
        if (!$localeCode) {
            return;
        }

        $timezone = static::normalize($timezone);

        if ($timezone === null) {
            static::query()->whereKey($localeCode)->delete();

            return;
        }

        if (!in_array($timezone, DateTimeZone::listIdentifiers(), true)) {
            throw new ValidationException([
                'nexus_timezone' => trans('xitara.nexus::settings.timezone.invalid'),
            ]);
        }

        static::query()->updateOrCreate(['locale_code' => $localeCode], ['timezone' => $timezone]);
    }

    public static function forgetLocaleCode(?string $localeCode): void
    {
        if ($localeCode) {
            static::query()->whereKey($localeCode)->delete();
        }
    }

    private static function normalize($timezone): ?string
    {
        if ($timezone === null || in_array($timezone, [0, '0'], true)) {
            return null;
        }

        $timezone = trim((string) $timezone);

        return $timezone === '' ? null : $timezone;
    }
}
