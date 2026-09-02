<?php

namespace Xitara\Nexus\Models;

use Config;
use Model;

/**
 * Config Model
 *
 * @property int                                            $id
 * @property string|null                                    $item
 * @property string|null                                    $value
 * @method static \Winter\Storm\Database\Collection<int, static> all($columns = ['*'])
 * @method static \Winter\Storm\Database\Collection<int, static> get($columns = ['*'])
 * @method static \Winter\Storm\Database\Builder|Settings        lists(string $column, string $key = null)
 * @method static \Winter\Storm\Database\Builder|Settings        newModelQuery()
 * @method static \Winter\Storm\Database\Builder|Settings        newQuery()
 * @method static \Winter\Storm\Database\Builder|Settings        orSearchWhere(string $term, string $columns = [], string $mode = 'all')
 * @method static \Winter\Storm\Database\Builder|Settings        query()
 * @method static \Winter\Storm\Database\Builder|Settings        searchWhere(string $term, string $columns = [], string $mode = 'all')
 * @method static \Winter\Storm\Database\Builder|Settings        whereId($value)
 * @method static \Winter\Storm\Database\Builder|Settings        whereItem($value)
 * @method static \Winter\Storm\Database\Builder|Settings        whereValue($value)
 * @mixin \Eloquent
 */
class Settings extends Model
{
    use \Winter\Storm\Database\Traits\Validation;

    public $implement = ['System.Behaviors.SettingsModel'];
    public $settingsCode = 'xitara_nexus_setting';
    public $settingsFields = 'fields.yaml';

    public $rules = [
        'default_email' => 'nullable|email',
    ];

    public $attachOne = [
        'menu_icon_uploaded' => ['\System\Models\File', 'public' => false],
    ];

    /**
     * Return the canonical recipient for future Xitara system notifications.
     * Empty Nexus values fall back to Winter's configured sender identity.
     *
     * @return array{email: string, name: string}
     */
    public static function getNotificationRecipient(): array
    {
        $email = trim((string) static::get('default_email', ''));
        $name = trim((string) static::get('default_email_name', ''));

        return [
            'email' => $email !== '' ? $email : (string) Config::get('mail.from.address', ''),
            'name' => $name !== '' ? $name : (string) Config::get('mail.from.name', ''),
        ];
    }
}
