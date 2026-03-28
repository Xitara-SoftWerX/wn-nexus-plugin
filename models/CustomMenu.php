<?php

namespace Xitara\Nexus\Models;

use Model;
use Str;

/**
 * CustomMenu Model
 *
 * @property int                                            $id
 * @property string|null                                    $name
 * @property string|null                                    $namespace
 * @property string|null                                    $slug
 * @property string|null                                    $links
 * @property int                                            $is_submenu
 * @property int                                            $is_active
 * @property \Illuminate\Support\Carbon|null                $created_at
 * @property \Illuminate\Support\Carbon|null                $updated_at
 * @method static \Winter\Storm\Database\Collection<int, static> all($columns = ['*'])
 * @method static \Winter\Storm\Database\Collection<int, static> get($columns = ['*'])
 * @method static \Winter\Storm\Database\Builder|CustomMenu      lists(string $column, string $key = null)
 * @method static \Winter\Storm\Database\Builder|CustomMenu      newModelQuery()
 * @method static \Winter\Storm\Database\Builder|CustomMenu      newQuery()
 * @method static \Winter\Storm\Database\Builder|CustomMenu      orSearchWhere(string $term, string $columns = [], string $mode = 'all')
 * @method static \Winter\Storm\Database\Builder|CustomMenu      query()
 * @method static \Winter\Storm\Database\Builder|CustomMenu      searchWhere(string $term, string $columns = [], string $mode = 'all')
 * @method static \Winter\Storm\Database\Builder|CustomMenu      whereCreatedAt($value)
 * @method static \Winter\Storm\Database\Builder|CustomMenu      whereId($value)
 * @method static \Winter\Storm\Database\Builder|CustomMenu      whereIsActive($value)
 * @method static \Winter\Storm\Database\Builder|CustomMenu      whereIsSubmenu($value)
 * @method static \Winter\Storm\Database\Builder|CustomMenu      whereLinks($value)
 * @method static \Winter\Storm\Database\Builder|CustomMenu      whereName($value)
 * @method static \Winter\Storm\Database\Builder|CustomMenu      whereNamespace($value)
 * @method static \Winter\Storm\Database\Builder|CustomMenu      whereSlug($value)
 * @method static \Winter\Storm\Database\Builder|CustomMenu      whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class CustomMenu extends Model
{
    use \Winter\Storm\Database\Traits\Validation;

    /**
     * @var string The database table used by the model.
     */
    public $table = 'xitara_nexus_custommenus';

    /**
     * @var array Guarded fields
     */
    protected $guarded = ['*'];

    /**
     * @var array Fillable fields
     */
    protected $fillable = [];

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
    protected $jsonable = ['links'];

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
    protected $dates = [
        'created_at',
        'updated_at',
    ];

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

    public function beforeSave()
    {
        /**
         * update code from xitara_nexus_menus
         */
        $success = Menu::where('code', 'xitara.custommenulist.' . $this->slug)
            ->update([
                'code' => 'xitara.custommenulist.' . Str::slug($this->name),
                'name' => $this->name,
            ]);

        $this->slug = Str::slug($this->name);
    }

    public function beforeDelete()
    {
        /**
         * delete code from xitara_nexus_menus
         */
    }
}
