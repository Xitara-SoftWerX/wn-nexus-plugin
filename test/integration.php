<?php

use Backend\Classes\BackendController;
use Backend\Classes\Controller as BackendControllerBase;
use Backend\Models\User;
use Backend\Widgets\ReportContainer;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Xitara\Nexus\Classes\BackendMenuAggregator;
use Xitara\Nexus\Classes\BackendUserPurger;
use Xitara\Nexus\Controllers\Dashboard;
use Xitara\Nexus\Models\CustomMenu;
use Xitara\Nexus\Models\LocaleTimezone;
use Xitara\Nexus\Models\Menu;

if (getenv('NEXUS_RUN_DB_TESTS') !== '1') {
    fwrite(STDERR, "Set NEXUS_RUN_DB_TESTS=1 to run transactional database integration checks.\n");
    exit(2);
}

$winterRoot = dirname(__DIR__, 4);

require $winterRoot . '/bootstrap/autoload.php';
$app = require $winterRoot . '/bootstrap/app.php';
$request = Illuminate\Http\Request::create('/backend/xitara/nexus/dashboard', 'GET');
$app->instance('request', $request);
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

function assertIntegration($condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

try {
    assertIntegration(
        Schema::hasTable('xitara_nexus_locale_timezones'),
        'The Nexus locale-time-zone migration has not been applied.',
    );
    assertIntegration(
        BackendUserPurger::isAvailable(),
        'The Nexus backend-user deletion migration has not been applied.',
    );
    assertIntegration(
        !Schema::hasColumn('winter_translate_locales', 'nexus_timezone'),
        'The legacy Winter.Translate time-zone column still exists.',
    );

    $now = Carbon::parse('2026-09-02 12:00:00');
    $eligibleUsers = User::onlyTrashed()
        ->whereNotNull(BackendUserPurger::REQUESTED_AT_COLUMN)
        ->where(
            BackendUserPurger::REQUESTED_AT_COLUMN,
            '<=',
            BackendUserPurger::expirationCutoff($now),
        )
        ->count();

    assertIntegration(
        $eligibleUsers === 0,
        'Refusing to exercise the purger while non-test accounts are eligible.',
    );

    DB::beginTransaction();

    try {
        $locale = Winter\Translate\Models\Locale::query()->orderBy('id')->first();
        assertIntegration($locale !== null, 'Winter.Translate has no locale fixture.');

        $locale->nexus_timezone = 'Europe/Berlin';
        $locale->save();

        assertIntegration(
            LocaleTimezone::forLocaleCode((string) $locale->code) === 'Europe/Berlin',
            'Locale time zone was not persisted in the Nexus table.',
        );
        assertIntegration(
            Xitara\Nexus\Plugin::getTimezone((string) $locale->code) === 'Europe/Berlin',
            'Locale time zone was not resolved through the Nexus API.',
        );

        $locale->nexus_timezone = '0';
        $locale->save();

        assertIntegration(
            LocaleTimezone::forLocaleCode((string) $locale->code) === null,
            'Selecting the system time zone did not remove the locale override.',
        );

        $menuName = 'Nexus test ' . Str::random(8);
        $customMenu = new CustomMenu();
        $customMenu->name = $menuName;
        $customMenu->namespace = 'Xitara\\Nexus\\Integration';
        $customMenu->is_submenu = true;
        $customMenu->is_active = true;
        $customMenu->links = [
            [
                'text' => 'Integration target',
                'link' => '/backend',
                'icon' => 'icon-check',
                'icon_image' => null,
                'is_blank' => false,
                'is_active' => true,
            ],
        ];
        $customMenu->save();

        assertIntegration(
            Menu::query()->whereKey($customMenu->getNexusGroupCode())->exists(),
            'Custom menu did not create its sorting record.',
        );

        $plugin = new Xitara\Nexus\Plugin($app);
        $permissions = $plugin->registerPermissions();
        assertIntegration(
            isset($permissions[$customMenu->getPermissionCode()]),
            'Canonical custom-menu permission was not registered.',
        );

        $aggregator = new BackendMenuAggregator();
        $collectCustomItems = new ReflectionMethod($aggregator, 'collectCustomItems');
        $collectCustomItems->setAccessible(true);
        $collectCustomItems->invoke($aggregator);
        $customItemsProperty = new ReflectionProperty($aggregator, 'nexusCustomItems');
        $customItemsProperty->setAccessible(true);
        $customItems = $customItemsProperty->getValue($aggregator);
        $itemCode = $customMenu->getNavigationNamespace() . '.' . Str::slug('Integration target');
        $customItem = $customItems[$itemCode] ?? null;

        assertIntegration($customItem !== null, 'Custom menu was not collected by the aggregator.');
        assertIntegration(
            in_array($customMenu->getPermissionCode(), $customItem['permissions'], true),
            'Custom menu is missing its canonical permission.',
        );
        assertIntegration(
            in_array($customMenu->getLegacyPermissionCode(), $customItem['permissions'], true),
            'Custom menu is missing its transition-release permission alias.',
        );

        $superuser = User::query()->where('is_superuser', true)->firstOrFail();
        BackendAuth::setUser($superuser);
        BackendController::$action = 'index';
        BackendController::$params = [];

        $dashboard = new Dashboard();
        $dashboard->index();
        assertIntegration(
            $dashboard->widget->reportContainer instanceof ReportContainer,
            'Authorized dashboard did not initialize Winter\'s report container.',
        );

        $fallbackDashboard = new Dashboard();
        $userProperty = (new ReflectionClass(BackendControllerBase::class))->getProperty('user');
        $userProperty->setAccessible(true);
        $userProperty->setValue(
            $fallbackDashboard,
            new class {
                public function hasAccess($permission): bool
                {
                    return false;
                }
            },
        );
        $fallbackDashboard->index();
        assertIntegration(
            !isset($fallbackDashboard->widget->reportContainer),
            'Dashboard fallback initialized report widgets without permission.',
        );

        try {
            $fallbackDashboard->index_onInitReportContainer();
            throw new RuntimeException(
                'Dashboard AJAX handler accepted a user without permission.',
            );
        } catch (HttpExceptionInterface $exception) {
            assertIntegration(
                $exception->getStatusCode() === 403,
                'Dashboard AJAX denial did not return 403.',
            );
        }

        $suffix = strtolower(Str::random(16));
        $testUser = new User();
        $testUser->login = 'nexus-integration-' . $suffix;
        $testUser->email = 'nexus-integration-' . $suffix . '@example.invalid';
        $testUser->first_name = 'Nexus';
        $testUser->last_name = 'Integration';
        $password = Str::random(32);
        $testUser->password = $password;
        $testUser->password_confirmation = $password;
        $testUser->is_activated = true;
        $testUser->save();
        $testUserId = (int) $testUser->getKey();

        BackendUserPurger::requestDeletion($testUser);
        $deletedUser = User::withTrashed()->findOrFail($testUserId);
        assertIntegration(
            $deletedUser->trashed(),
            'Self-deactivation did not soft-delete the backend user.',
        );
        assertIntegration(
            $deletedUser->{BackendUserPurger::REQUESTED_AT_COLUMN} !== null,
            'Self-deactivation did not mark the deletion request.',
        );

        $deletedUser->restore();
        $restoredUser = User::query()->findOrFail($testUserId);
        assertIntegration(
            $restoredUser->{BackendUserPurger::REQUESTED_AT_COLUMN} === null,
            'Restoring the account did not cancel permanent deletion.',
        );

        BackendUserPurger::requestDeletion($restoredUser);
        DB::table('backend_users')
            ->where('id', $testUserId)
            ->update([
                BackendUserPurger::REQUESTED_AT_COLUMN => $now->copy()->subDays(15),
            ]);

        assertIntegration(
            BackendUserPurger::purgeExpired($now) === 1,
            'The purge did not permanently remove exactly the expired test account.',
        );
        assertIntegration(
            User::withTrashed()->find($testUserId) === null,
            'The expired test account still exists after the purge.',
        );

        echo "Nexus transactional integration checks passed.\n";
    } finally {
        DB::rollBack();
    }
} catch (Throwable $exception) {
    fwrite(STDERR, $exception->getMessage() . "\n");
    exit(1);
}
