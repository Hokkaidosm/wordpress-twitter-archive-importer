<?php
/** @var WP_Database $wpdb */
global $wpdb;
if (!defined("WP_UNINSTALL_PLUGIN")) exit();
require_once(plugin_dir_path(__FILE__) . "twitter-archive-importer.php");
$twitterArchiveImporter = new TwitterArchiveImporter();
$wpdb->query(sprintf("DROP TABLE IF EXISTS %s", $twitterArchiveImporter->getProcessedIdsTableName()));
$wpdb->query(sprintf("DROP TABLE IF EXISTS %s", $twitterArchiveImporter->getPostIdsTableName()));

delete_option(TwitterArchiveImporterConsts::dbVersionOptionName);
delete_option(TwitterArchiveImporterConsts::optionName);