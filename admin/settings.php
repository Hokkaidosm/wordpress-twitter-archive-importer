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

?>
<div class="wrap">
    <h1><?= esc_html__("Twitter Archive Importer Settings", "twitter-archive-importer") ?></h1>
    <?php settings_errors(); ?>
    <form method="POST" action="options.php">
        <?php settings_fields(TwitterArchiveImporterConsts::optionGroupName) ?>
        <?php do_settings_sections(TwitterArchiveImporterConsts::optionAdminPage) ?>
        <?= submit_button(__("Save Settings", "twitter-archive-importer")) ?>
    </form>
</div>