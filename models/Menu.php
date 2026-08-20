<?php

namespace Xitara\Nexus\Models;

use Model;

/**
 * Menu Model
 *
 * @property string|null                                    $code
 * @property string|null                                    $owner
 * @property string|null                                    $main_menu_code
 * @property string|null                                    $source_type
 * @property bool                                           $is_enabled
 * @property string|null                                    $name
 * @property int|null                                       $sort_order
 * @property \Illuminate\Support\Carbon|null                $last_seen_at
 * @method static \Winter\Storm\Database\Collection<int, static> all($columns = ['*'])
 * @method static \Winter\Storm\Database\Collection<int, static> get($columns = ['*'])
 * @method static \Winter\Storm\Database\Builder|Menu            lists(string $column, string $key = null)
 * @method static \Winter\Storm\Database\Builder|Menu            newModelQuery()
 * @method static \Winter\Storm\Database\Builder|Menu            newQuery()
 * @method static \Winter\Storm\Database\Builder|Menu            orSearchWhere(string $term, string $columns = [], string $mode = 'all')
 * @method static \Winter\Storm\Database\Builder|Menu            query()
 * @method static \Winter\Storm\Database\Builder|Menu            searchWhere(string $term, string $columns = [], string $mode = 'all')
 * @method static \Winter\Storm\Database\Builder|Menu            whereCode($value)
 * @method static \Winter\Storm\Database\Builder|Menu            whereName($value)
 * @method static \Winter\Storm\Database\Builder|Menu            whereSortOrder($value)
 * @mixin \Eloquent
 */
class Menu extends Model
{
    use \Winter\Storm\Database\Traits\Validation;
    use \Winter\Storm\Database\Traits\Sortable;

    /**
     * @var string The database table used by the model.
     */
    public $table = 'xitara_nexus_menus';

    /**
     * @var array Guarded fields
     */
    protected $guarded = ['*'];

    /**
     * @var array Fillable fields
     */
    protected $fillable = [
        'code',
        'owner',
        'main_menu_code',
        'source_type',
        'is_enabled',
        'name',
        'sort_order',
        'last_seen_at',
    ];

    /**
     * @var array Validation rules for attributes
     */
    public $rules = [];

    /**
     * @var array Attributes to be cast to native types
     */
    protected $casts = [
        'is_enabled' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * @var array Attributes to be cast to JSON
     */
    protected $jsonable = [];

    /**
     * @var array Attributes to be appended to the API representation of the model (ex. toArray())
     */
    protected $appends = [];

    /**
     * @var array Attributes to be removed from the API representation of the model (ex. toArray())
     */
    protected $hidden = [];

    /**
     * @var array Attributes to be cast to Argon (Carbon) instances
     */
    protected $dates = ['last_seen_at'];

    /**
     * @var array Relations
     */
    public $hasOne = [];
    public $hasMany = [];
    public $belongsTo = [];
    public $belongsToMany = [];
    public $morphTo = [];
    public $morphOne = [];
    public $morphMany = [];
    public $attachOne = [];
    public $attachMany = [];

    protected $primaryKey = 'code';
    public $timestamps = false;
    public $incrementing = false;

    /**
     * Build a compact, stable key that fits the legacy primary key column.
     */
    public static function makeNavigationCode(string $owner, string $mainMenuCode): string
    {
        return 'nav:'.sha1(strtoupper($owner).'/'.$mainMenuCode);
    }

    public function getDisplayNameAttribute(): string
    {
        if ($this->name !== null && $this->name !== '') {
            return trans($this->name);
        }

        if ($this->owner && $this->main_menu_code) {
            return $this->owner.' / '.$this->main_menu_code;
        }

        return (string) $this->code;
    }
}
