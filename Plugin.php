<?php

namespace Xitara\Nexus;

use Backend;
use Backend\Controllers\Users;
use Backend\Models\Preference;
use Backend\Models\User;
use Backend\Models\UserRole;
use System\Classes\PluginBase;
use Xitara\Nexus\Classes\BackendMenuAggregator;
use Xitara\Nexus\Classes\BackendMenuRegistry;
use Xitara\Nexus\Models\CustomMenu;
use Xitara\Nexus\Models\Menu;
use Xitara\Nexus\Models\Settings as NexusSettings;

class Plugin extends PluginBase
{
    /**
     * @var array
     */
    public $require = [];

    /**
     * Returns information about this plugin.
     *
     * @return array
     */
    public function pluginDetails()
    {
        return [
            'name' => 'xitara.nexus::lang.plugin.name',
            'description' => 'xitara.nexus::lang.plugin.description',
            'author' => 'xitara.nexus::lang.plugin.author',
            'homepage' => 'xitara.nexus::lang.plugin.homepage',
            'icon' => '',
            'iconSvg' => 'plugins/xitara/nexus/assets/images/icon-nexus.svg',
        ];
    }

    public function register()
    {
        \BackendMenu::registerContextSidenavPartial(
            'Xitara.Nexus',
            'nexus',
            '$/xitara/nexus/partials/_sidebar.htm'
        );

        $this->registerConsoleCommand('xitara.fakeblog', 'Xitara\Nexus\Console\FakeBlog');
        $this->registerConsoleCommand('xitara.fakeuser', 'Xitara\Nexus\Console\FakeUser');
        $this->registerConsoleCommand('nexus.test', 'Xitara\Nexus\Console\Test');
    }

    /**
     * @return null
     */
    public function boot()
    {
        /**
         * include helpers.
         */
        include_once \dirname(__FILE__).'/helpers.php';

        // Check if we are currently in backend module.
        if (!\App::runningInBackend()) {
            return;
        }

        /*
         * remove gravatar call
         */
        // User::extend(function ($model) {
        // $model->bindEvent('model.afterFetch', function () use ($model) {
        // $file = new \System\Models\File;
        // $path = plugins_path('xitara/nexus/assets/images/avatar.png');
        // $model->avatar = $file->fromFile($path);
        // });
        // });

        $menuAggregator = new BackendMenuAggregator();
        \Event::listen('backend.menu.extendItems', function ($navigationManager) use ($menuAggregator) {
            $menuAggregator->extend($navigationManager);
        });

        \Event::listen('backend.page.beforeDisplay', function ($controller, $action, $params) {
            // Controllers set their context at different points in the request.
            // Remap it once more immediately before the layout is rendered.
            \BackendMenu::listMainMenuItems();
            BackendMenuRegistry::remapCurrentContext();

            if (NexusSettings::get('is_compact_display')) {
                $controller->addCss(\Config::get('cms.pluginsPath').'/xitara/nexus/assets/css/compact.css');
            }

            $controller->addCss(\Config::get('cms.pluginsPath').'/xitara/nexus/assets/css/backend.css');
            $controller->addJs(\Config::get('cms.pluginsPath').'/xitara/nexus/assets/js/backend.js');

            if ($controller instanceof Backend\Controllers\Index) {
                return \Redirect::to('/backend/xitara/nexus/dashboard');
            }
        });

        /*
         * remove original dashboard
         */
        \Event::listen('backend.menu.extendItems', function ($navigationManager) {
            $navigationManager->removeMainMenuItem('Winter.Backend', 'dashboard');
        });

        User::extend(function ($model) {
            /*
             * remove roles publisher and developer if user is not an superuser
             */
            $model->addDynamicMethod('getMyRoleOptions', function ($model) {
                $result = [];

                $user = \BackendAuth::getUser();

                if ($user->is_superuser == 1) {
                    $roles = UserRole::all();
                }

                if ($user->is_superuser == 0) {
                    $roles = UserRole::where('is_system', 0)->get();
                }

                foreach ($roles as $role) {
                    $result[$role->id] = [$role->name, $role->description];
                }

                return $result;
            });
        });

        /*
         * extend other plugins if needed
         */
        \Event::listen('backend.form.extendFieldsBefore', function ($widget) {
            /*
             * set available role options in backend user setting
             */
            if ($widget->getController() instanceof Users && $widget->model instanceof User) {
                $widget->tabs['fields']['role']['options'] = 'getMyRoleOptions';
            }
        });

        /*
         * add new toolbor for disable group and permission tab for non superuser
         */
        Users::extend(function ($controller) {
            /*
             * soft delete user account
             */
            $controller->addDynamicMethod('onDeleteAccount', function () {
                $user = \BackendAuth::getUser();
                \Event::fire('backend.user.beforeDelete', [$user]);
                $user->delete();
                \BackendAuth::logout($user);
                \Flash::success('Account erfolgreich deaktiviert');

                return \Redirect::to('/backend');
            });
        });

        /*
         * remove groups and permission columns from non superuser in list
         */
        Users::extendListColumns(function ($list, $model) {
            if (\BackendAuth::getUser()->isSuperUser()) {
                return;
            }

            // $list->removeColumn('permissions');
            // $list->removeColumn('groups');
        });

        /*
         * remove groups and permission tabs from non superuser in form
         */
        Users::extendFormFields(function ($form, $model, $context) {
            if (\BackendAuth::getUser()->isSuperUser()) {
                return;
            }

            // $form->removeField('permissions');
            // $form->removeField('groups');

            if (\Request::segment(4) == 'myaccount') {
                $form->addTabFields([
                    'deleteAccount' => [
                        'tab' => 'backend::lang.user.account',
                        'label' => 'xitara.nexus::lang.deleteAccount.label',
                        'comment' => 'xitara.nexus::lang.deleteAccount.comment',
                        'type' => 'partial',
                        'path' => '$/xitara/nexus/partials/_deleteaccount.htm',
                    ],
                ]);
            }
        });

        /*
         * add timezone dropdown to translate-plugin
         */
        $this->bootTranslateExtend();
    }

    public function registerSettings()
    {
        if (($category = NexusSettings::get('menu_text')) == '') {
            $category = 'xitara.nexus::core.settings.name';
        }

        return [
            'settings' => [
                'category' => $category,
                'label' => 'xitara.nexus::lang.settings.label',
                'description' => 'xitara.nexus::lang.settings.description',
                'icon' => 'icon-wrench',
                'class' => 'Xitara\Nexus\Models\Settings',
                'order' => 0,
                'permissions' => ['xitara.nexus.settings'],
            ],
            'menu_configuration' => [
                'category' => $category,
                'label' => 'xitara.nexus::lang.menu_configuration.label',
                'description' => 'xitara.nexus::lang.menu_configuration.description',
                'icon' => 'icon-bars',
                'url' => \Backend::url('xitara/nexus/menu/reorder'),
                'order' => 10,
                'permissions' => ['xitara.nexus.menu'],
            ],
        ];
    }

    /**
     * Registers any back-end permissions used by this plugin.
     *
     * @return array
     */
    public function registerPermissions()
    {
        $permissions = [
            'xitara.nexus.mainmenu' => [
                'tab' => 'Xitara Nexus',
                'label' => 'xitara.nexus::permissions.mainmenu',
            ],
            'xitara.nexus.settings' => [
                'tab' => 'Xitara Nexus',
                'label' => 'xitara.nexus::permissions.settings',
            ],
            'xitara.nexus.dashboard' => [
                'tab' => 'Xitara Nexus',
                'label' => 'xitara.nexus::permissions.dashboard',
            ],
            'xitara.nexus.menu' => [
                'tab' => 'Xitara Nexus',
                'label' => 'xitara.nexus::permissions.menu',
            ],
            'xitara.nexus.custommenus' => [
                'tab' => 'Xitara Nexus',
                'label' => 'xitara.nexus::permissions.custommenus',
            ],
        ];

        $menus = CustomMenu::orderBy('name', 'asc')->get();

        if ($menus !== null) {
            foreach ($menus as $menu) {
                $permissions['xitara.nexus.custommenu.'.$menu->slug] = [
                    'tab' => 'Xitara Nexus Custom Menus',
                    'label' => $menu->name,
                ];
            }
        }

        return $permissions;
    }

    /**
     * Registers back-end navigation items for this plugin.
     *
     * @return array
     */
    public function registerNavigation()
    {
        $nexus = NexusSettings::instance();
        $iconSvg = '';

        if ($nexus->menu_icon_uploaded) {
            $iconSvg = $nexus->menu_icon_uploaded->getPath();
        } elseif (NexusSettings::get('menu_icon_text', '') == '') {
            $iconSvg = 'plugins/xitara/nexus/assets/images/icon-nexus.svg';
        }

        if (($label = NexusSettings::get('menu_text')) == '') {
            $label = 'xitara.nexus::lang.submenu.label';
        }

        return [
            'nexus' => [
                'label' => $label,
                'url' => \Backend::url('xitara/nexus/dashboard'),
                'icon' => NexusSettings::get('menu_icon_text', 'icon-leaf'),
                'iconSvg' => $iconSvg,
                'permissions' => ['xitara.nexus.*'],
                'order' => 50,
                'sideMenu' => static::baseSideMenuItems(),
            ],
        ];
    }

    public function registerSchedule($schedule)
    {
        $schedule->call(function () {
            \Log::debug('Nexus: Schedule call');
        })->everyMinute();
    }

    /**
     * grab sidemenu items
     * $inject contains addidtional menu-items with the following strcture.
     *
     * name = [
     *     label => string|'placeholder', // placeholder only
     *     url => [string], (full backend url)
     *     icon => [string],
     *     'attributes' => [
     *         'target' => [string],
     *         'placeholder' => true|false, // placeholder after elm
     *         'keywords' => [string],
     *         'description' => [string],
     *         'group' => [string], // group the items and set the heading of group
     *     ],
     * ]
     *
     * name -> unique name
     * group -> name to sort menu items
     * label -> shown name in menu
     * url -> url relative to backend
     * icon -> icon left of label
     * attribures -> array (optional)
     *     target -> _blank|_self (optional)
     *     keywords -> only for searching (optional)
     *     description -> showed under label (optional)
     *
     * @autor   mburghammer
     *
     * @date    2018-05-15T20:49:04+0100
     *
     * @version 0.0.3
     *
     * @since   0.0.1
     * @since   0.0.2 added groups
     * @since   0.0.3 added attributes
     */
    public static function getSideMenu(string $owner, string $code)
    {
        /*
         * Backwards compatibility for plugins that still contribute their own
         * injectSideMenu() definitions. New plugins should use registerNavigation().
         */
        \Event::listen('backend.menu.extendItems', function ($manager) use ($owner, $code) {
            (new BackendMenuAggregator())->addLegacyPluginItems($manager, $owner, $code);
        });
    }

    protected static function baseSideMenuItems(): array
    {
        if (($group = NexusSettings::get('menu_text')) == '') {
            $group = 'xitara.nexus::lang.submenu.label';
        }

        return [
            'nexus.dashboard' => [
                'label' => 'xitara.nexus::lang.nexus.dashboard',
                'url' => \Backend::url('xitara/nexus/dashboard'),
                'icon' => 'icon-dashboard',
                'permissions' => [
                    'xitara.nexus.mainmenu',
                    'xitara.nexus.dashboard',
                ],
                'attributes' => [
                    'group' => $group,
                ],
                'order' => 0,
            ],
            'nexus.menu' => [
                'label' => 'xitara.nexus::lang.nexus.menu',
                'url' => \Backend::url('xitara/nexus/menu/reorder'),
                'icon' => 'icon-sort',
                'permissions' => ['xitara.nexus.menu'],
                'attributes' => [
                    'group' => $group,
                ],
                'order' => 1,
            ],
            'nexus.custommenus' => [
                'label' => 'xitara.nexus::lang.custommenu.label',
                'url' => \Backend::url('xitara/nexus/custommenus'),
                'icon' => 'icon-link',
                'permissions' => ['xitara.nexus.custommenus'],
                'attributes' => [
                    'group' => $group,
                ],
                'order' => 2,
            ],
        ];
    }

    /**
     * @return mixed
     */
    public static function getMenuOrder(string $code): int
    {
        $item = Menu::find($code);

        if ($item === null) {
            return 9999;
        }

        return $item->sort_order;
    }

    /**
     * inject into sidemenu.
     *
     * @autor   mburghammer
     *
     * @date    2020-06-26T21:13:34+02:00
     *
     * @see Xitara\Nexus::getSideMenu
     *
     * @return array sidemenu-data
     */
    public static function injectSideMenu()
    {
        // Log::debug(__METHOD__);

        $custommenus = CustomMenu::where('is_submenu', 1)
            ->where('is_active', 1)
            ->get();

        $inject = [];
        foreach ($custommenus as $custommenu) {
            $count = 0;
            // Log::debug('-- ' . $custommenu->slug);
            // Log::debug('>> ' . $custommenu->namespace);

            $namespace = $custommenu->namespace !== null
                ? strtolower(str_replace('\\', '.', $custommenu->namespace))
                : $custommenu->slug.'.custommenulist';

            // Log::debug('== ' . $namespace);

            foreach ($custommenu->links as $i => $link) {
                if ($link['is_active'] == 1) {
                    $icon = $iconSvg = null;

                    if (isset($link['icon']) && $link['icon'] != '') {
                        $icon = $link['icon'];
                    }

                    if (isset($link['icon_image']) && $link['icon_image'] != '') {
                        $iconSvg = url(\Config::get('cms.storage.media.path').$link['icon_image']);
                    }

                    $inject[$namespace.'.'.\Str::slug($link['text'])] = [
                        'label' => $link['text'],
                        'url' => $link['link'],
                        'icon' => $icon ?? null,
                        'iconSvg' => $iconSvg,
                        'permissions' => [
                            $namespace.'.'.$custommenu->slug,
                        ],
                        'attributes' => [
                            'group' => $namespace.'.'.$custommenu->slug,
                            'groupLabel' => $custommenu->name,
                            'target' => ($link['is_blank'] == 1) ? '_blank' : null,
                            'keywords' => $link['keywords'] ?? null,
                            'description' => $link['description'] ?? null,
                        ],
                        'order' => self::getMenuOrder($custommenu->getNexusGroupCode()) + $count++,
                    ];
                }
            }
        }

        // Log::debug($inject);

        return $inject;
    }

    /**
     * Extend translate plugin.
     */
    private function bootTranslateExtend()
    {
        if (class_exists("\Winter\Translate\Models\Locale")) {
            \Winter\Translate\Models\Locale::extend(function ($model) {
                $model->addFillable([
                    'nexus_timezone',
                ]);
            });

            /*
             * add dropdown
             */
            \Winter\Translate\Controllers\Locales::extendFormFields(function ($widget) {
                if (!$widget->model instanceof \Winter\Translate\Models\Locale) {
                    return;
                }

                if ($widget->isNested) {
                    return;
                }

                $configFile = __DIR__.'/config/timezone.yaml';
                $config = \Yaml::parse(\File::get($configFile));
                $widget->addFields($config['fields']);
            });

            /*
             * add dropdown options
             */
            \Winter\Translate\Models\Locale::extend(function ($model) {
                $model->addDynamicMethod('getNexusTimezoneOptions', function () {
                    $timezones = (new Preference())->getTimezoneOptions();
                    array_unshift($timezones, e(trans('xitara.nexus::settings.no_timezone')));

                    return $timezones;
                });
            });

            /*
             * set timezone to null if option is 0 (no timezone)
             */
            \Winter\Translate\Models\Locale::extend(function ($model) {
                $model->bindEvent('model.beforeSave', function () use ($model) {
                    \Log::debug($model->nexus_timezone);

                    if ($model->nexus_timezone == '0') {
                        $model->nexus_timezone = null;
                    }
                });
            });
        }
    }

    public static function getTimezone($localecode = null): string
    {
        return self::timezone($localecode);
    }

    /**
     * @return mixed
     */
    private static function timezone($localecode): string
    {
        if ($localecode === null) {
            $localecode = \Winter\Translate\Classes\Translator::instance()->getLocale();
        }

        $locale = \Winter\Translate\Models\Locale::findByCode($localecode);

        return $locale->nexus_timezone ?? \Config::get('app.timezone');
    }

    public static function slug($title, $separator = '-', $language = null)
    {
        if ($language === null) {
            $language = \Session::get('locale');
        }

        if ($language === null) {
            $language = \Config::get('app.locale');
        }

        return \Str::slug($title, $separator, $language);
    }
}
