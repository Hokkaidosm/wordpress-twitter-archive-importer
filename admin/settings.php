<div class="wrap">
    <h1><?= __("Twitter Archive Importer Settings", "twitter-archive-importer") ?></h1>
    <?php settings_errors(); ?>
    <form method="POST" action="options.php">
        <?php settings_fields(TwitterArchiveImporterConsts::optionGroupName) ?>
        <?php do_settings_sections(TwitterArchiveImporterConsts::optionAdminPage) ?>
        <?= submit_button(__("Save Settings", "twitter-archive-importer")) ?>
    </form>
</div>