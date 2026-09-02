<?php

namespace Xitara\Nexus;

use Backend;
use Backend\Controllers\Preferences as BackendPreferences;
use Backend\Controllers\Users;
use Backend\Models\Preference;
use Backend\Models\User;
use Backend\Models\UserRole;
use System\Classes\PluginBase;
use Throwable;
use Xitara\Nexus\Classes\BackendMenuAggregator;
use Xitara\Nexus\Classes\BackendMenuRegistry;
use Xitara\Nexus\Classes\BackendUserPurger;
use Xitara\Nexus\Classes\ExceptionEditorLinkBuilder;
use Xitara\Nexus\Classes\ExceptionViewCompatibility;
use Xitara\Nexus\Models\CustomMenu;
use Xitara\Nexus\Models\LocaleTimezone;
use Xitara\Nexus\Models\Menu;
use Xitara\Nexus\Models\Settings as NexusSettings;

class Plugin extends PluginBase
{
    /**
     * @var array
     */
    public $require = [];

    public function pluginDetails(): array
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

    public function register(): void
    {
        \BackendMenu::registerContextSidenavPartial(
            'Xitara.Nexus',
            'nexus',
            '$/xitara/nexus/partials/_sidebar.htm',
        );
    }

    public function boot(): void
    {
        include_once __DIR__ . '/helpers.php';

        $exceptionViewCompatibility = $this->bootExtendedExceptionView();
        $this->bootTranslateExtend();

        if (!\App::runningInBackend()) {
            return;
        }

        $this->bootExceptionEditorPreferences();
        $this->bootExceptionCompatibilityWarning($exceptionViewCompatibility);

        $menuAggregator = new BackendMenuAggregator();
        \Event::listen('backend.menu.extendItems', function ($navigationManager) use (
            $menuAggregator,
        ) {
            $menuAggregator->extend($navigationManager);
        });

        \Event::listen('backend.page.beforeDisplay', function ($controller, $action, $params) {
            // Controllers set their context at different points in the request.
            // Remap it once more immediately before the layout is rendered.
            \BackendMenu::listMainMenuItems();
            BackendMenuRegistry::remapCurrentContext();

            if (NexusSettings::get('is_compact_display')) {
                $controller->addCss(
                    \Config::get('cms.pluginsPath') . '/xitara/nexus/assets/css/compact.css',
                );
            }

            $controller->addCss(
                \Config::get('cms.pluginsPath') . '/xitara/nexus/assets/css/backend.css',
            );
            $controller->addJs(
                \Config::get('cms.pluginsPath') . '/xitara/nexus/assets/js/backend.js',
            );

            if ($controller instanceof Backend\Controllers\Index) {
                return \Redirect::to('/backend/xitara/nexus/dashboard');
            }
        });

        // The Nexus dashboard keeps Winter's widget UI inside the shared side menu.
        \Event::listen('backend.menu.extendItems', function ($navigationManager) {
            $navigationManager->removeMainMenuItem('Winter.Backend', 'dashboard');
        });

        User::extend(function ($model) {
            if (BackendUserPurger::isAvailable()) {
                $model->bindEvent('model.beforeRestore', function () use ($model): void {
                    $model->{BackendUserPurger::REQUESTED_AT_COLUMN} = null;
                });
            }

            // Non-superusers may only assign non-system roles.
            $model->addDynamicMethod('getMyRoleOptions', function ($model) {
                $result = [];

                $user = \BackendAuth::getUser();
                if ($user === null) {
                    return $result;
                }

                if ($user->isSuperUser()) {
                    $roles = UserRole::all();
                } else {
                    $roles = UserRole::where('is_system', 0)->get();
                }

                foreach ($roles as $role) {
                    $result[$role->id] = [$role->name, $role->description];
                }

                return $result;
            });
        });

        \Event::listen('backend.form.extendFieldsBefore', function ($widget) {
            if ($widget->getController() instanceof Users && $widget->model instanceof User) {
                $widget->tabs['fields']['role']['options'] = 'getMyRoleOptions';
            }
        });

        Users::extend(function ($controller) {
            $controller->addDynamicMethod('onDeleteAccount', function () {
                $user = \BackendAuth::getUser();
                BackendUserPurger::requestDeletion($user);
                \BackendAuth::logout();
                \Flash::success(trans('xitara.nexus::lang.deleteAccount.success'));

                return \Redirect::to('/backend');
            });
        });

        Users::extendFormFields(function ($form, $model, $context) {
            if (\BackendAuth::getUser()->isSuperUser()) {
                return;
            }

            if (\Request::segment(4) === 'myaccount') {
                $form->addTabFields([
                    'deleteAccount' => [
                        'tab' => 'backend::lang.user.account',
                        'label' => 'xitara.nexus::lang.deleteAccount.label',
                        'comment' => 'xitara.nexus::lang.deleteAccount.comment',
                        'type' => 'partial',
                        'path' => '$/xitara/nexus/partials/_deleteaccount.php',
                    ],
                ]);
            }
        });
    }

    /**
     * Registers the compatible Nexus exception view without affecting the core fallback.
     */
    private function bootExtendedExceptionView(): ExceptionViewCompatibility
    {
        $compatibility = new ExceptionViewCompatibility();

        try {
            if (\App::runningInConsole() || !$this->isExtendedExceptionViewConfigured()) {
                return $compatibility;
            }

            $status = $compatibility->inspect();
            if ($status['compatible']) {
                \View::prependNamespace('system', \dirname($status['plugin_view_path']));
            }
        } catch (Throwable $exception) {
            // The original Winter namespace remains untouched on every failure.
        }

        return $compatibility;
    }

    /**
     * Adds per-user editor settings to Winter's existing preference model.
     */
    private function bootExceptionEditorPreferences(): void
    {
        \Event::listen('backend.form.extendFields', function ($widget) {
            if (
                !($widget->getController() instanceof BackendPreferences) ||
                !($widget->model instanceof Preference) ||
                $widget->isNested
            ) {
                return;
            }

            $editorOptions = ['' => trans('xitara.nexus::settings.exception.editor_none')];
            $editorOptions += ExceptionEditorLinkBuilder::getPresetOptions();
            $editorOptions[ExceptionEditorLinkBuilder::EDITOR_CUSTOM] = trans(
                'xitara.nexus::settings.exception.editor_custom',
            );

            $widget->addTabFields([
                'nexus_exception_editor_section' => [
                    'label' => 'xitara.nexus::settings.exception.editor_section',
                    'comment' => 'xitara.nexus::settings.exception.editor_section_comment',
                    'type' => 'section',
                    'tab' => 'xitara.nexus::settings.exception.editor_tab',
                ],
                ExceptionEditorLinkBuilder::PREFERENCE_EDITOR => [
                    'label' => 'xitara.nexus::settings.exception.editor',
                    'comment' => 'xitara.nexus::settings.exception.editor_comment',
                    'type' => 'dropdown',
                    'options' => $editorOptions,
                    'span' => 'left',
                    'tab' => 'xitara.nexus::settings.exception.editor_tab',
                ],
                ExceptionEditorLinkBuilder::PREFERENCE_CUSTOM_NAME => [
                    'label' => 'xitara.nexus::settings.exception.custom_name',
                    'comment' => 'xitara.nexus::settings.exception.custom_name_comment',
                    'type' => 'text',
                    'span' => 'right',
                    'tab' => 'xitara.nexus::settings.exception.editor_tab',
                    'trigger' => [
                        'action' => 'show',
                        'field' => ExceptionEditorLinkBuilder::PREFERENCE_EDITOR,
                        'condition' => 'value[custom]',
                    ],
                ],
                ExceptionEditorLinkBuilder::PREFERENCE_CUSTOM_TEMPLATE => [
                    'label' => 'xitara.nexus::settings.exception.custom_template',
                    'comment' => 'xitara.nexus::settings.exception.custom_template_comment',
                    'type' => 'text',
                    'span' => 'full',
                    'placeholder' => 'xdebug://open?url=file://{file}&line={line}',
                    'tab' => 'xitara.nexus::settings.exception.editor_tab',
                    'trigger' => [
                        'action' => 'show',
                        'field' => ExceptionEditorLinkBuilder::PREFERENCE_EDITOR,
                        'condition' => 'value[custom]',
                    ],
                ],
                'nexus_exception_path_mapping_section' => [
                    'label' => 'xitara.nexus::settings.exception.path_mapping',
                    'comment' => 'xitara.nexus::settings.exception.path_mapping_comment',
                    'type' => 'section',
                    'tab' => 'xitara.nexus::settings.exception.editor_tab',
                ],
                ExceptionEditorLinkBuilder::PREFERENCE_SERVER_PATH => [
                    'label' => 'xitara.nexus::settings.exception.server_path',
                    'comment' => 'xitara.nexus::settings.exception.server_path_comment',
                    'type' => 'text',
                    'span' => 'left',
                    'tab' => 'xitara.nexus::settings.exception.editor_tab',
                ],
                ExceptionEditorLinkBuilder::PREFERENCE_LOCAL_PATH => [
                    'label' => 'xitara.nexus::settings.exception.local_path',
                    'comment' => 'xitara.nexus::settings.exception.local_path_comment',
                    'type' => 'text',
                    'span' => 'right',
                    'tab' => 'xitara.nexus::settings.exception.editor_tab',
                ],
            ]);
        });
    }

    /**
     * Warns once per session when the configured override cannot be used.
     */
    private function bootExceptionCompatibilityWarning(
        ExceptionViewCompatibility $compatibility,
    ): void {
        \Event::listen('backend.page.beforeDisplay', function () use ($compatibility) {
            try {
                if (!$this->isExtendedExceptionViewConfigured()) {
                    return;
                }

                $status = $compatibility->inspect();
                if ($status['compatible']) {
                    return;
                }

                $warningKey =
                    'xitara.nexus.exception_view_warning.' . ($status['hash'] ?? $status['reason']);
                if (\Session::get($warningKey, false)) {
                    return;
                }

                $message = trans('xitara.nexus::settings.exception.incompatible_warning');
                if ($status['hash']) {
                    $message .=
                        ' ' .
                        trans('xitara.nexus::settings.exception.detected_hash', [
                            'hash' => $status['hash'],
                        ]);
                }

                $winterBuild = $this->getWinterBuild();
                if ($winterBuild !== null) {
                    $message .=
                        ' ' .
                        trans('xitara.nexus::settings.exception.winter_build', [
                            'build' => $winterBuild,
                        ]);
                }

                \Flash::warning($message);
                \Session::put($warningKey, true);
            } catch (Throwable $exception) {
                // A warning must never interfere with normal backend rendering.
            }
        });
    }

    private function isExtendedExceptionViewConfigured(): bool
    {
        return in_array(
            NexusSettings::get('extended_exception_view_enabled', false),
            [true, 1, '1', 'true'],
            true,
        );
    }

    private function getWinterBuild(): ?string
    {
        try {
            $build = \System\Models\Parameter::get('system::core.build');

            return $build !== null && $build !== '' ? (string) $build : null;
        } catch (Throwable $exception) {
            return null;
        }
    }

    public function registerSettings(): array
    {
        $category = (string) NexusSettings::get('menu_text', '');
        if ($category === '') {
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

    public function registerPermissions(): array
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
                $permissions[$menu->getPermissionCode()] = [
                    'tab' => 'Xitara Nexus Custom Menus',
                    'label' => $menu->name,
                ];
            }
        }

        return $permissions;
    }

    public function registerNavigation(): array
    {
        $nexus = NexusSettings::instance();
        $iconSvg = '';

        if ($nexus->menu_icon_uploaded) {
            $iconSvg = $nexus->menu_icon_uploaded->getPath();
        } elseif ((string) NexusSettings::get('menu_icon_text', '') === '') {
            $iconSvg = 'plugins/xitara/nexus/assets/images/icon-nexus.svg';
        }

        $label = (string) NexusSettings::get('menu_text', '');
        if ($label === '') {
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

    public function registerSchedule($schedule): void
    {
        $schedule
            ->call([BackendUserPurger::class, 'purgeExpired'])
            ->daily()
            ->name('xitara.nexus.purge-expired-backend-users')
            ->withoutOverlapping();
    }

    /**
     * Register the legacy menu bridge for an external plugin.
     *
     * @deprecated since 2.4.0; use native registerNavigation() definitions instead
     */
    public static function getSideMenu(string $owner, string $code): void
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
        $group = (string) NexusSettings::get('menu_text', '');
        if ($group === '') {
            $group = 'xitara.nexus::lang.submenu.label';
        }

        return [
            'nexus.dashboard' => [
                'label' => 'xitara.nexus::lang.nexus.dashboard',
                'url' => \Backend::url('xitara/nexus/dashboard'),
                'icon' => 'icon-dashboard',
                'permissions' => ['xitara.nexus.mainmenu', 'xitara.nexus.dashboard'],
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
     * @deprecated since 2.4.0; native side-menu item orders are local to their source
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
     * Extend translate plugin.
     */
    private function bootTranslateExtend(): void
    {
        if (
            !class_exists('\Winter\Translate\Models\Locale') ||
            !\Schema::hasTable('winter_translate_locales') ||
            !\Schema::hasTable('xitara_nexus_locale_timezones')
        ) {
            return;
        }

        \Winter\Translate\Models\Locale::extend(function ($model) {
            $originalLocaleCode = null;

            $model->addFillable(['nexus_timezone']);
            $model->addPurgeable('nexus_timezone');

            $model->bindEvent('model.afterFetch', function () use ($model): void {
                $model->nexus_timezone = LocaleTimezone::forLocaleCode((string) $model->code);
            });

            $model->bindEvent('model.beforeSave', function () use (
                $model,
                &$originalLocaleCode,
            ): void {
                $originalLocaleCode = $model->exists ? (string) $model->getOriginal('code') : null;
            });

            $model->bindEvent('model.afterSave', function () use (
                $model,
                &$originalLocaleCode,
            ): void {
                $localeCode = (string) $model->code;

                LocaleTimezone::storeForLocaleCode(
                    $localeCode,
                    $model->getOriginalPurgeValue('nexus_timezone'),
                );

                if ($originalLocaleCode && $originalLocaleCode !== $localeCode) {
                    LocaleTimezone::forgetLocaleCode($originalLocaleCode);
                }
            });

            $model->bindEvent('model.afterDelete', function () use ($model): void {
                LocaleTimezone::forgetLocaleCode((string) $model->code);
            });
        });

        if (!\App::runningInBackend()) {
            return;
        }

        \Winter\Translate\Controllers\Locales::extendFormFields(function ($widget) {
            if (!($widget->model instanceof \Winter\Translate\Models\Locale) || $widget->isNested) {
                return;
            }

            $configFile = __DIR__ . '/config/timezone.yaml';
            $config = \Yaml::parse(\File::get($configFile));
            $widget->addFields($config['fields']);
        });

        \Winter\Translate\Models\Locale::extend(function ($model) {
            $model->addDynamicMethod('getNexusTimezoneOptions', function () {
                $timezones = (new Preference())->getTimezoneOptions();
                array_unshift($timezones, e(trans('xitara.nexus::settings.no_timezone')));

                return $timezones;
            });
        });
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
        if (
            !class_exists('\Winter\Translate\Models\Locale') ||
            !\Schema::hasTable('winter_translate_locales') ||
            !\Schema::hasTable('xitara_nexus_locale_timezones')
        ) {
            return \Config::get('app.timezone');
        }

        if ($localecode === null) {
            $localecode = \Winter\Translate\Classes\Translator::instance()->getLocale();
        }

        return LocaleTimezone::forLocaleCode((string) $localecode) ?: \Config::get('app.timezone');
    }

    public static function slug($title, $separator = '-', $language = null): string
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
