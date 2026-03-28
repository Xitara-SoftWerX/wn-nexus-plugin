<?php

namespace Xitara\Nexus\Models;

use Model;

/**
 * Menu Model
 *
 * @property string|null                                    $code
 * @property string|null                                    $name
 * @property int|null                                       $sort_order
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
    protected $fillable = ['code', 'sort_order'];

    /**
     * @var array Validation rules for attributes
     */
    public $rules = [];

    /**
     * @var array Attributes to be cast to native types
     */
    protected $casts = [];

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
    protected $dates = [];

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
}
