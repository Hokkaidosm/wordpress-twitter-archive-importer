<?php
/*
Plugin Name: Twitter Archive Importer
Plugin URI: https://github.com/Hokkaidosm/wordpress-twitter-archive-importer/
Description: This plugin provides a function for import Twitter archive file.
Version: 0.2
Author: Hokkaidosm
Author URI: https://hokkaidosm.net
Licence: GPL v3 or later
Licence URI: https://spdx.org/licenses/GPL-3.0-or-later.html
Text Domain: twitter-archive-importer
Domain Path: /languages
 */

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

require_once(plugin_dir_path(__FILE__) . "constants.php");
require_once(plugin_dir_path(__FILE__) . "vendor/autoload.php");

add_action("init", function () {
    load_plugin_textdomain("twitter-archive-importer", false, dirname(plugin_basename(__FILE__)) . "/languages");
});

class TwitterArchiveImporter
{
    private $options;

    /** @var string $processedIdsTableName 処理済みツイートID最大値管理テーブル名 */
    private $processedIdsTableName;
    /** @var string $postIdsTableName 記事ID管理テーブル名 */
    private $postIdsTableName;

    public function __construct()
    {
        /** @var WP_Database $wpdb */
        global $wpdb;
        load_plugin_textdomain("twitter-archive-importer", false, dirname(plugin_basename(__FILE__)) . "/languages");

        add_action("admin_menu", function () {
            add_menu_page(
                __("Twitter Archive Importer", "twitter-archive-importer"),
                __("Twitter Archive Importer", "twitter-archive-importer"),
                "manage_options",
                plugin_dir_path(__FILE__) . "admin/index.php",
                null,
                "dashicons-twitter",
                20
            );

            add_submenu_page(
                plugin_dir_path(__FILE__) . "admin/index.php",
                __("Import Twitter Archive", "twitter-archive-importer"),
                __("Import", "twitter-archive-importer"),
                "manage_options",
                plugin_dir_path(__FILE__) . "admin/import.php",
                null,
                25
            );

            add_submenu_page(
                plugin_dir_path(__FILE__) . "admin/index.php",
                __("Twitter Archive Importer Setting", "twitter-archive-importer"),
                __("Setting", "twitter-archive-importer"),
                "manage_options",
                plugin_dir_path(__FILE__) . "admin/settings.php",
                null,
                30
            );
        });

        $this->options = get_option(TwitterArchiveImporterConsts::optionName);
        $this->setDefaultOptions();

        add_action("admin_init", function () {
            register_setting(
                TwitterArchiveImporterConsts::optionGroupName,
                TwitterArchiveImporterConsts::optionName,
                [$this, "sanitize"]
            );

            add_settings_section(
                TwitterArchiveImporterConsts::optionSectionName,
                __("Setting", "twitter-archive-importer"),
                function () {
                },
                TwitterArchiveImporterConsts::optionAdminPage
            );

            add_settings_field(
                TwitterArchiveImporterConsts::categoryId,
                __("Category", "twitter-archive-importer"),
                function () {
                    $this->category_callback();
                },
                TwitterArchiveImporterConsts::optionAdminPage,
                TwitterArchiveImporterConsts::optionSectionName
            );

            add_settings_field(
                TwitterArchiveImporterConsts::postTitleTemplate,
                __("Post title template", "twitter-archive-importer"),
                function () {
                    $this->post_title_template_callback();
                },
                TwitterArchiveImporterConsts::optionAdminPage,
                TwitterArchiveImporterConsts::optionSectionName
            );
        });

        $this->processedIdsTableName = $wpdb->prefix . "twitter_archive_importer_processed_ids";
        $this->postIdsTableName = $wpdb->prefix . "twitter_archive_importer_post_ids";

        register_activation_hook(__FILE__, function () {
            $this->updateDataBase();
        });
    }

    /**
     * オプションのアップデート
     * ※自動的にアップデートされないので注意
     */
    public function updateOptions()
    {
        update_option(TwitterArchiveImporterConsts::optionName, $this->options);
    }

    public function setDefaultOptions()
    {
        // アップデートフラグ
        $update = false;

        if (!isset($this->options[TwitterArchiveImporterConsts::categoryId])) {
            // カテゴリ初期値設定
            $categories = get_categories([
                "hide_empty" => false, "orderby" => "id"
            ]);
            $categoryId = $categories[0]->cat_ID;
            $this->options[TwitterArchiveImporterConsts::categoryId] = $categoryId;
            $update = true;
        }

        if (!isset($this->options[TwitterArchiveImporterConsts::postTitleTemplate])) {
            // 投稿タイトルテンプレート初期値設定
            $this->options[TwitterArchiveImporterConsts::postTitleTemplate] = __(
                /* translators: This is the default post title template. 1: username, 2: date */
                '%1$s\'s Twitter log of %2$s',
                "twitter-archive-importer"
            );
            $update = true;
        }

        if ($update) {
            $this->updateOptions();
        }
    }

    /** 記事のカテゴリIDを返す */
    public function getCategoryId(): int
    {
        return $this->options[TwitterArchiveImporterConsts::categoryId];
    }

    /** 投稿タイトルテンプレートを返す */
    public function getPostTitleTemplate(): string
    {
        return $this->options[TwitterArchiveImporterConsts::postTitleTemplate];
    }

    public function sanitize($input): array
    {
        $sanitary_values = [];
        if (isset($input[TwitterArchiveImporterConsts::categoryId]) && is_numeric($input[TwitterArchiveImporterConsts::categoryId])) {
            $sanitary_values[TwitterArchiveImporterConsts::categoryId] = intval($input[TwitterArchiveImporterConsts::categoryId]);
        }
        if (isset($input[TwitterArchiveImporterConsts::postTitleTemplate])) {
            $sanitary_values[TwitterArchiveImporterConsts::postTitleTemplate] = $input[TwitterArchiveImporterConsts::postTitleTemplate];
        }
        return $sanitary_values;
    }

    public function category_callback()
    {
        wp_dropdown_categories([
            "orderby" => "id",
            "hide_empty" => false,
            "name" => TwitterArchiveImporterConsts::optionName . "[" . TwitterArchiveImporterConsts::categoryId . "]",
            "selected" => $this->getCategoryId(),
            "required" => true
        ]);
    }

    public function post_title_template_callback()
    {
        $name = TwitterArchiveImporterConsts::optionName . "[" . TwitterArchiveImporterConsts::postTitleTemplate . "]";
?>
        <input type="text" name="<?= $name ?>" value="<?= esc_attr($this->getPostTitleTemplate()) ?>" required><br />
        <?= __(
            /* translators: This message is a description of the "Post title template". It explains that %1$s is replaced by the Twitter username, and %2$s by the date. */
            '"%1$s" replaces to user name of Twitter, "%2$s" replaces to date.',
            "twitter-archive-importer"
        ) ?>
<?php
    }

    /**
     * プラグインディレクトリ名
     */
    public function getPluginDirName(): string
    {
        return basename(dirname(__FILE__));
    }

    /** 
     * 処理済みツイートID最大値を返す。未処理のユーザに対しては0を返す
     * @param string $accountId アカウントID
     * @return int 処理済みツイートID最大値 / 未処理のユーザに対しては0を返す
     */
    public function getProcessedId($accountId): int
    {
        /** @var WP_Database $wpdb */
        global $wpdb;
        $record = $wpdb->get_results(
            $wpdb->prepare("SELECT tweetId FROM {$this->processedIdsTableName} WHERE userId = %s", $accountId)
        );
        if (empty($record)) {
            return 0;
        }
        return $record[0]->tweetId;
    }

    /**
     * 処理済みツイートID最大値を設定する
     * @param string $accountId アカウントID
     * @param int $tweetId 処理済みツイートID最大値
     */
    public function setProcessedId($accountId, $tweetId)
    {
        /** @var WP_Database $wpdb */
        global $wpdb;

        if (0 === $this->getProcessedId($accountId)) {
            // 新規
            $wpdb->insert($this->processedIdsTableName, ["userId" => $accountId, "tweetId" => $tweetId]);
        } else {
            // 更新
            $wpdb->update($this->processedIdsTableName, ["tweetId" => $tweetId], ["userId" => $accountId]);
        }
    }

    /**
     * 指定したユーザと日付の記事IDを取得する。無い場合はnullを返す
     * @param string $accountId アカウントID
     * @param string $date 日付
     */
    public function getPostId($accountId, $date): int | null
    {
        /** @var WP_Database $wpdb */
        global $wpdb;
        $record = $wpdb->get_results(
            $wpdb->prepare("SELECT postId FROM {$this->postIdsTableName} WHERE userId = %s AND date = %s", $accountId, $date)
        );
        if (empty($record)) {
            return null;
        }
        return $record[0]->postId;
    }

    /**
     * 指定したユーザと日付の記事IDを設定する
     * @param string $accountId アカウントID
     * @param string $date 日付
     * @param int $postId 記事ID
     */
    public function setPostId($accountId, $date, $postId)
    {
        /** @var WP_Database $wpdb */
        global $wpdb;

        if (is_null($this->getPostId($accountId, $date))) {
            // 新規
            $wpdb->insert($this->postIdsTableName, ["userId" => $accountId, "date" => $date, "postId" => $postId]);
        } else {
            // 更新
            $wpdb->update($this->postIdsTableName, ["postId" => $postId], ["userId" => $accountId, "date" => $date]);
        }
    }

    public function updateDataBase()
    {
        $dbVersion = 1; // 更新時にここを変える
        $installedDbVersion = get_option(TwitterArchiveImporterConsts::dbVersionOptionName);
        if ($dbVersion != $installedDbVersion) {
            $createProcessedIdTableSql = <<<SQL
            CREATE TABLE %s (
                userId text(30) NOT NULL ,
                tweetId bigint(20) NOT NULL DEFAULT '0' ,
                PRIMARY KEY (userId(30))
            );
            SQL;
            $createProcessedIdTableSql = sprintf($createProcessedIdTableSql, $this->processedIdsTableName);

            $createPostIdsTableSql = <<<SQL
            CREATE TABLE %s (
                userId text(30) NOT NULL,
                date text(10) NOT NULL,
                postId bigint(20) NOT NULL,
                PRIMARY KEY (userId(30),date(10))
            );
            SQL;
            $createPostIdsTableSql = sprintf($createPostIdsTableSql, $this->postIdsTableName);

            require_once(ABSPATH . "wp-admin/includes/upgrade.php");
            dbDelta($createProcessedIdTableSql . PHP_EOL . $createPostIdsTableSql);
            update_option(TwitterArchiveImporterConsts::dbVersionOptionName, $dbVersion);
        }
    }

    public function getProcessedIdsTableName(): string
    {
        return $this->processedIdsTableName;
    }

    public function getPostIdsTableName(): string
    {
        return $this->postIdsTableName;
    }
}

$twitterArchiveImporter = new TwitterArchiveImporter();
