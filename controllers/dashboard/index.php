<?php

Block::put('sidepanel');
$this->makePartial('$/xitara/nexus/partials/_sidebar.htm');
Block::endPut();

Block::put('body');

if ($this->user->hasAccess('xitara.nexus.dashboard')): ?>
    <?= Form::open(['class' => 'layout-relative dashboard-container']) ?>
        <div id="dashReportContainer" class="report-container loading">
            <!-- Loading -->
            <div class="loading-indicator-container">
                <div class="loading-indicator indicator-center">
                    <span></span>
                    <div><?= e(trans('backend::lang.list.loading')) ?></div>
                </div>
            </div>
        </div>
    <?= Form::close() ?>

    <?php Block::put('head'); ?>
    <script>
        Snowboard.ready(() => {
            Snowboard.request(null, 'onInitReportContainer', {
                success: () => {
                    $('#dashReportContainer').removeClass('loading');
                },
            });
        });
    </script>
    <?php Block::endPut(true); ?>
<?php else: ?>
    <?= \Xitara\Nexus\Models\Settings::get('dashboard_text') ?>
<?php endif;
Block::endPut();
