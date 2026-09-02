<div data-control="toolbar">
    <a href="<?= Backend::url('backend/users/create') ?>" class="btn btn-primary oc-icon-plus">
        <?= e(trans('backend::lang.user.new')) ?>
    </a>
    <?php if ($this->user->isSuperUser()): ?>
        <a href="<?= Backend::url(
            'backend/userroles',
        ) ?>" class="btn btn-default oc-icon-address-card">
            <?= e(trans('backend::lang.user.role.list_title')) ?>
        </a>
        <a href="<?= Backend::url('backend/usergroups') ?>" class="btn btn-default oc-icon-group">
            <?= e(trans('backend::lang.user.group.list_title')) ?>
        </a>
    <?php endif; ?>
</div>
