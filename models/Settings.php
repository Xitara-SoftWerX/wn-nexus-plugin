<?php

namespace Xitara\Nexus\Models;

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
    public $implement = ['System.Behaviors.SettingsModel'];
    public $settingsCode = 'xitara_nexus_setting';
    public $settingsFields = 'fields.yaml';

    public $attachOne = [
        'menu_icon_uploaded' => [
            '\System\Models\File',
            'public' => false,
        ],
    ];

}
