<?php
/*
Plugin Name: Twitter Archive Importer
Description: TwitterアーカイブをWordPressに取り込むプラグイン
Version: 0.1
Author: Hokkaidosm
Author URI: https://hokkaidosm.net
Licence: MIT License
Licence URI: https://spdx.org/licenses/MIT.html
Text Domain: twitter-archive-importer
 */

require_once(plugin_dir_path(__FILE__) . "constants.php");

class TwitterArchiveImporter
{
    private $options;

    public function __construct()
    {
        add_action("admin_menu", function () {
            add_menu_page(
                "twitter_archive_importer",
                __("Twitter Archive Importer", "twitter-archive-importer"),
                "manage_options",
                plugin_dir_path(__FILE__) . "admin/index.php",
                null,
                plugin_dir_url(__FILE__) . "images/icon_twitter_archive_importer.png",
                20
            );

            add_submenu_page(
                plugin_dir_path(__FILE__) . "admin/index.php",
                "twitter_archive_importer_import",
                __("Import", "twitter-archive-importer"),
                "manage_options",
                plugin_dir_path(__FILE__) . "admin/import.php",
                null,
                25
            );

            add_submenu_page(
                plugin_dir_path(__FILE__) . "admin/index.php",
                "twitter_archive_importer_setting",
                __("Setting", "twitter-archive-importer"),
                "manage_options",
                "setting",
                function () {
                    require_once(plugin_dir_path(__FILE__) . "admin/settings.php");
                },
                30
            );
        });

        $this->options = get_option(TwitterArchiveImporterOptionName::optionName);
        $this->setDefaultOptions();

        add_action("admin_init", function () {
            register_setting(
                TwitterArchiveImporterOptionName::optionGroupName,
                TwitterArchiveImporterOptionName::optionName,
                [$this, "sanitize"]
            );

            add_settings_section(
                TwitterArchiveImporterOptionName::optionSectionName,
                __("Setting", "twitter-archive-importer"),
                function () {
                },
                TwitterArchiveImporterOptionName::optionAdminPage
            );

            add_settings_field(
                TwitterArchiveImporterOptionName::categoryId,
                __("Category"),
                function () {
                    $this->category_callback();
                },
                TwitterArchiveImporterOptionName::optionAdminPage,
                TwitterArchiveImporterOptionName::optionSectionName
            );
        });
    }

    public function setDefaultOptions()
    {
        // 設定アップデートフラグ
        $update = false;
        if (!isset($this->options[TwitterArchiveImporterOptionName::categoryId])) {
            // カテゴリ初期値設定
            $categories = get_categories([
                "hide_empty" => false, "orderby" => "id"
            ]);
            $categoryId = $categories[0]->cat_ID;
            $this->options[TwitterArchiveImporterOptionName::categoryId] = $categoryId;
            $update = true;
        }

        if ($update) {
            update_option(TwitterArchiveImporterOptionName::optionName, $this->options);
        }
    }

    public function getCategoryId(): int
    {
        return $this->options[TwitterArchiveImporterOptionName::categoryId];
    }

    public function sanitize($input): array
    {
        var_dump($input);
        $sanitary_values = [];
        if (isset($input[TwitterArchiveImporterOptionName::categoryId])) {
            $sanitary_values[TwitterArchiveImporterOptionName::categoryId] = $input[TwitterArchiveImporterOptionName::categoryId];
        }
        return $sanitary_values;
    }

    public function category_callback()
    {
        var_dump($this->options);
        wp_dropdown_categories([
            "orderby" => "id",
            "hide_empty" => false,
            "name" => TwitterArchiveImporterOptionName::categoryId,
            "selected" => $this->getCategoryId()
        ]);
    }
}

$twitterArchiveImporter = new TwitterArchiveImporter();