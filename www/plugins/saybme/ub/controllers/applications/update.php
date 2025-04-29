<?php Block::put('breadcrumb') ?>
    <ul>
        <li><a href="<?= Backend::url('saybme/ub/applications') ?>">Applications</a></li>
        <li><?= e($this->pageTitle) ?></li>
    </ul>
<?php Block::endPut() ?>

<?php if (!$this->fatalError): ?>

    <?= $this->formRenderDesign() ?>


<?php else: ?>
    <p class="flash-message static error"><?= e(trans($this->fatalError)) ?></p>
    <p><a href="<?= Backend::url('saybme/ub/applications') ?>" class="btn btn-default"><?= e(trans('backend::lang.form.return_to_list')) ?></a></p>
<?php endif ?>