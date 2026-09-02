<?php

namespace Xitara\Nexus\Controllers;

use Backend\Classes\Controller;
use Backend\Traits\InspectableContainer;
use Backend\Widgets\ReportContainer;
use BackendMenu;
use Cms\Classes\Theme;

/**
 * Displays Winter's report-widget dashboard inside the Nexus menu context.
 */
class Dashboard extends Controller
{
    use InspectableContainer;

    /**
     * Every authenticated backend user may open the landing page. The view
     * decides whether to render report widgets or the configured fallback.
     *
     * @var array
     */
    public $requiredPermissions = [];

    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('Xitara.Nexus', 'nexus', 'nexus.dashboard');
        $this->addCss('/modules/backend/assets/css/dashboard/dashboard.css', 'core');
    }

    public function index(): void
    {
        if ($this->user->hasAccess('xitara.nexus.dashboard')) {
            $this->initReportContainer();
        }

        $this->pageTitle = 'backend::lang.dashboard.menu_label';
    }

    public function index_onInitReportContainer(): array
    {
        if (!$this->user->hasAccess('xitara.nexus.dashboard')) {
            abort(403, trans('backend::lang.page.access_denied.label'));
        }

        $this->initReportContainer();

        return ['#dashReportContainer' => $this->widget->reportContainer->render()];
    }

    /**
     * Prepare Winter's normal dashboard container while allowing the existing
     * optional theme-level default-widget configuration.
     */
    protected function initReportContainer(): void
    {
        $config = '~/modules/backend/controllers/index/config_dashboard.yaml';

        if ($theme = Theme::getActiveTheme()) {
            $themeConfig = themes_path($theme->getDirName() . '/config/dashboard.yaml');

            if (is_file($themeConfig)) {
                $config = $themeConfig;
            }
        }

        new ReportContainer($this, $config);
    }
}
