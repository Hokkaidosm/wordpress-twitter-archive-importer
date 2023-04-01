<?php

if ('POST' === $_SERVER['REQUEST_METHOD']) {
    // TODO インポート処理
}
?>
<div class="wrap">
    <h1><?= __("Import Twitter Archive", "twitter-archive-importer") ?></h1>
    <form method="POST">
        <?= submit_button(__("Import", "twitter-archive-importer")) ?>
    </form>
</div>