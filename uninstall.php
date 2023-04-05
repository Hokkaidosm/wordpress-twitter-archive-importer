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

/** @var WP_Database $wpdb */
global $wpdb;
if (!defined("WP_UNINSTALL_PLUGIN")) exit();
require_once(plugin_dir_path(__FILE__) . "twitter-archive-importer.php");
$twitterArchiveImporter = new TwitterArchiveImporter();
$wpdb->query(sprintf("DROP TABLE IF EXISTS %s", $twitterArchiveImporter->getProcessedIdsTableName()));
$wpdb->query(sprintf("DROP TABLE IF EXISTS %s", $twitterArchiveImporter->getPostIdsTableName()));

delete_option(TwitterArchiveImporterConsts::dbVersionOptionName);
delete_option(TwitterArchiveImporterConsts::optionName);