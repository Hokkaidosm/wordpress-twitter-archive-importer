<?php
/**
 * @var TwitterArchiveImporter $twitterArchiveImporter
 */
// ファイルアクセス確認
$redirect = wp_nonce_url(admin_url("admin.php?page=" . urlencode($twitterArchiveImporter->getPluginDirName() . "/admin/index.php")));
if (false === ($credentials = request_filesystem_credentials($redirect))) { // 権限取得
    return; // stop processing here
}
if (!WP_Filesystem($credentials)) {
    request_filesystem_credentials($redirect, '', true, false, null);
    return;
}
/** @var WP_Filesystem_Base $wp_filesystem */
global $wp_filesystem;
$upload_dir = $wp_filesystem->find_folder(WP_PLUGIN_DIR . "/" . $twitterArchiveImporter->getPluginDirName() . "/" . TwitterArchiveImporterConsts::uploadDirName);
if (!$wp_filesystem->exists($upload_dir)) {
    // アップロードフォルダがない！
    $wp_filesystem->mkdir($upload_dir);
}
?>
<div class="wrap">
    <h1><?= __("Twitter Archive Importer", "twitter-archive-importer") ?></h1>
    <pre><?php var_dump($upload_dir); ?></pre>
</div>