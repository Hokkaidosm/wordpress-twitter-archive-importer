<?php

if ('POST' === $_SERVER['REQUEST_METHOD']) {
    // TODO 保存処理
}

?>
<div class="wrap">
    <h1><?= __("Twitter Archive Importer Settings", "twitter-archive-importer") ?></h1>
    <form method="POST" action="options.php">
        <?php settings_fields(TwitterArchiveImporterOptionName::optionGroupName) ?>
        <?php do_settings_sections(TwitterArchiveImporterOptionName::optionAdminPage) ?>
        <?= submit_button(__("Save Settings", "twitter-archive-importer")) ?>
    </form>
</div>