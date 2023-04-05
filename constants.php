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
 * 定数定義
 */
class TwitterArchiveImporterConsts
{
    const optionName = "twitter_archive_importer_options";
    const optionGroupName = "twitter_archive_importer_options_group";
    const optionSectionName = "twitter_archive_importer_options_section";
    const optionAdminPage = "twitter-archive-importer-admin";

    /** カテゴリID */
    const categoryId = "categoryId";

    /** 記事名テンプレート */
    const postTitleTemplate = "postTitleTemplate";

    /** アップロード先フォルダ名 */
    const uploadDirName = "upload";

    /** 処理済みID */
    const processedIds = "processedIds";

    /** 日付->記事ID */
    const postIds = "postIds";

    /** DBバージョンのオプション名 */
    const dbVersionOptionName = "twitter_archive_importer_db_version";
}
