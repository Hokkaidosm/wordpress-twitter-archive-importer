<?php

/**
 *     Copyright (C) 2023 Hokkaidosm
 * 
 *     This program is free software: you can redistribute it and/or modify
 *     it under the terms of the GNU General Public License as published by
 *     the Free Software Foundation, either version 3 of the License, or
 *     (at your option) any later version.
 * 
 *     This program is distributed in the hope that it will be useful,
 *     but WITHOUT ANY WARRANTY; without even the implied warranty of
 *     MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *     GNU General Public License for more details.
 * 
 *     You should have received a copy of the GNU General Public License
 *     along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

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
    <h1><?= esc_html__("Twitter Archive Importer", "twitter-archive-importer") ?></h1>
    <h2><?= esc_html__("Welcome to Twitter Archive Importer!", "twitter-archive-importer") ?></h2>
    <p><?= esc_html__("This plugin provides a function for import Twitter archive file.", "twitter-archive-importer") ?></p>
    <h2><?= esc_html__("Operation Procedure", "twitter-archive-importer") ?></h2>
    <ol>
        <li>
            <a href="<?= esc_attr__(
                            /* translators: This url is Twitter's help page: "How to download your Twitter archive" */
                            "https://help.twitter.com/en/managing-your-account/how-to-download-your-twitter-archive",
                            "twitter-archive-importer"
                        ) ?>" target="_blank"><?= esc_html__("Download yout Twitter's archive.", "twitter-archive-importer") ?></a>
        </li>
        <li>
            <?= esc_html(sprintf(__(
                /* translators: %s replaces to upload path */
                "Upload your archive file to \"%s\".",
                "twitter-archive-importer"
            ), $upload_dir)) ?>
        </li>
        <li>
            <?= esc_html__("Go to \"Import\" menu, and select the archive file.", "twitter-archive-importer") ?>
        </li>
        <li>
            <?= esc_html__("Then click \"Import\" button, and wait a while.", "twitter-archive-importer") ?><br />
            <?= esc_html__("Note: The time it takes to import depends on the number of tweets since the last import. The first import in particular can take a significant amount of time.", "twitter-archive-importer") ?>
        </li>
    </ol>
    <h2><?= esc_html__("Contributions", "twitter-archive-importer") ?></h2>
    <p>
        <?= __(
            /* translators: This message is output in HTML without escaping. */
            "This plugin welcomes contributions on <a href=\"https://github.com/Hokkaidosm/wordpress-twitter-archive-importer/\" target=\"_blank\">GitHub</a>.",
            "twitter-archive-importer"
        ) ?>
    </p>
</div>