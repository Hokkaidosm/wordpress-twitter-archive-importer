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

/** @var TwitterArchiveImporter $twitterArchiveImporter */

/** oEmbedエンドポイント */
const oEmbedEndpoint = "https://publish.twitter.com/oembed";

function remove_js_header($js): string
{
    return preg_replace("/^.+ = \[/", "[", $js, 1);
}

// ファイルアクセス確認
$redirect = wp_nonce_url(admin_url("admin.php?page=" . urlencode($twitterArchiveImporter->getPluginDirName() . "/admin/import.php")));
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

/** ステップ */
enum ProcessStep
{
    /** ファイル選択 */
    case SelectFile;
    /** インポート */
    case Imported;
    /** ファイル削除 */
    case Deleted;
}

/** @var ProcessStep $processStep */
$processStep = ProcessStep::SelectFile;

/** ファイル名のフィールド名 */
const fileNameFieldName = "fileName";
/** 現在のステップのフィールド名 */
const currentStepFieldName = "currentStep";
/** 削除ボタンname */
const deleteButtonName = "delete";
/** 非削除ボタンname */
const nonDeleteButtonName = "nonDelete";

if ('POST' === $_SERVER['REQUEST_METHOD']) {
    check_admin_referer("import");
    switch ($_POST[currentStepFieldName]) {
        case ProcessStep::SelectFile->name:
            $processStep = ProcessStep::Imported;
            break;
        case ProcessStep::Imported->name:
            $processStep = ProcessStep::Deleted;
            break;
        case ProcessStep::Deleted->name:
            $processStep = ProcessStep::SelectFile;
            break;
        default:
            die;
    }
}
?>
<div class="wrap">
    <h1><?= esc_html__("Import Twitter Archive", "twitter-archive-importer") ?></h1>
    <?php
    if ($processStep == ProcessStep::SelectFile) :
        selectFilePage();
    elseif ($processStep == ProcessStep::Imported) :
        $fileName = sanitize_file_name($_POST[fileNameFieldName]);
        $fileDir = $upload_dir . $fileName;
        $result = importArchive($fileDir);
    ?>
        <h2><?= esc_html__("Import Result", "twitter-archive-importer") ?></h2>
        <p style="font-weight: bold">
            <?php if ($result->isSuccess()) : ?>
                <?= esc_html__("Import finished successful.", "twitter-archive-importer") ?>
            <?php else : ?>
                <?= esc_html__("Import failed, please see log following:", "twitter-archive-importer") ?>
            <?php endif; ?>
        </p>
        <?php if ($result->isSuccess()) : ?>
            <form method="POST">
                <p>
                    <?= esc_html(sprintf(__(
                        /* translators: %s replaces to filename */
                        "Would you like to delete \"%s\"?",
                        "twitter-archive-importer"
                    ), $fileName)) ?>
                </p>
                <p>
                    <input type="hidden" name="<?= fileNameFieldName ?>" value="<?= esc_attr($fileName) ?>">
                    <input type="hidden" name="<?= currentStepFieldName ?>" value="<?= $processStep->name ?>">
                    <?php wp_nonce_field("import") ?>
                    <?php submit_button(__("Yes"), "delete", deleteButtonName, false) ?>&nbsp;
                    <?php submit_button(__("No"), "secondary", nonDeleteButtonName, false) ?>
                </p>
            </form>
        <?php endif; ?>
        <?php foreach ($result->getLog() as $log) : ?>
            <p><?= $log ?></p>
        <?php endforeach; ?>
        <?php if (!$result->isSuccess()) : ?>
            <form method="POST">
                <p>
                    <input type="hidden" name="<?= currentStepFieldName ?>" value="<?= $processStep->name ?>">
                    <?php wp_nonce_field("import") ?>
                    <?php submit_button(__("Return to select file page", "twitter-archive-importer"), "primary", "", false) ?>
                </p>
            </form>
        <?php endif; ?>
    <?php
    elseif ($processStep == ProcessStep::Deleted) :
        if (isset($_POST[deleteButtonName])) {
            $fileName = sanitize_file_name($_POST[fileNameFieldName]);
            $fileDir = $upload_dir . $fileName;
            if ($wp_filesystem->delete($fileDir)) {
                add_settings_error("twitter-archive-importer", "delete_file_status", sprintf(__(
                    /* translators: %s replaces to filename */
                    "Deleted \"%s\".",
                    "twitter-archive-importer"
                ), $fileName), "success");
            } else {
                add_settings_error("twitter-archive-importer", "delete_file_status", sprintf(__(
                    /* translators: %s replaces to filename */
                    "Failed to delete \"%s\".",
                    "twitter-archive-importer"
                ), $fileName), "error");
            }
        }
        $processStep = ProcessStep::SelectFile;
        selectFilePage();
    ?>
    <?php
    endif;
    ?>
</div>

<?php

class TwitterArchiveImporterImportResult
{
    /**
     * 成否
     * @var bool $success
     */
    private $success = false;

    /**
     * ログ
     * @var array $log
     */
    private $log = [];

    /**
     * ログ記録
     * @param string $message ログ文字列
     */
    public function putLog($message)
    {
        array_push($this->log, $message);
    }

    /**
     * 成功とする
     */
    public function success()
    {
        $this->success = true;
    }

    /**
     * 失敗とする
     */
    public function failure()
    {
        $this->success = false;
    }

    /**
     * 成否取得
     * @return bool 成否（成功=true）
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * ログ取得
     * @return array ログ
     */
    public function getLog(): array
    {
        return $this->log;
    }
}

function selectFilePage()
{
    /**
     * @var WP_Filesystem_Base $wp_filesystem
     */
    global $wp_filesystem, $upload_dir, $processStep;
    settings_errors();

    $file_list = $wp_filesystem->dirlist($upload_dir);
    $file_list = array_filter($file_list, function ($file_name) {
        return strtolower(pathinfo($file_name, PATHINFO_EXTENSION)) === "zip";
    }, ARRAY_FILTER_USE_KEY);
    if (empty($file_list)) :
?>
        <p><?= esc_html__("Archive file is not found.", "twitter-archive-importer") ?></p>
        <p><?= esc_html(sprintf(__(
                /* translators: %s replaces to upload path */
                "Please upload archive file to : \"%s\".",
                "twitter-archive-importer"
            ), $upload_dir)) ?></p>
    <?php
    else :
    ?>
        <form method="POST">
            <p><?= esc_html__("Select archive file to import: ", "twitter-archive-importer") ?></p>
            <p>
                <select name="<?= fileNameFieldName ?>" required size="<?= count($file_list) ?>">
                    <?php foreach ($file_list as $file) : ?>
                        <option><?= htmlspecialchars($file["name"]) ?></option>
                    <?php endforeach; ?>
                </select>
            </p>
            <input type="hidden" name="<?= currentStepFieldName ?>" value="<?= $processStep->name ?>">
            <?php wp_nonce_field("import") ?>
            <?php submit_button(__("Import", "twitter-archive-importer")) ?>
        </form>
<?php endif;
}

function importArchive($fileDir): TwitterArchiveImporterImportResult
{
    /**
     * @var WP_Filesystem_Base $wp_filesystem
     * @var TwitterArchiveImporter $twitterArchiveImporter
     */
    global $wp_filesystem, $twitterArchiveImporter;
    /** @var TwitterArchiveImporterImportResult $result 結果 */
    $result = new TwitterArchiveImporterImportResult();

    if (!$wp_filesystem->exists($fileDir)) {
        $msg = esc_html(sprintf(__(
            /* translators: %s replaces to specified file name */
            "\"%s\" is not found.",
            "twitter-archive-importer"
        ), basename($fileDir)));
        $result->putLog($msg);
        return $result;
    }

    // 設定されたカテゴリはあるか
    if (empty(get_category($twitterArchiveImporter->getCategoryId()))) {
        $result->putLog(esc_html__("Selected category is not found.", "twitter-archive-importer"));
        return $result;
    }

    $extract_to = $wp_filesystem->find_folder(WP_PLUGIN_DIR . "/" . $twitterArchiveImporter->getPluginDirName() . "/" . TwitterArchiveImporterConsts::uploadDirName . "/" . md5(uniqid())) . "/";
    try {
        if (!$wp_filesystem->exists($extract_to)) {
            if (!$wp_filesystem->mkdir($extract_to)) {
                $result->putLog(esc_html__("Extract folder cannot make.", "twitter-archive-importer"));
                return $result;
            }
        } else {
            $result->putLog(esc_html__("Extract folder is exist.", "twitter-archive-importer"));
            return $result;
        }

        if (unzip_file($fileDir, $extract_to) !== true) {
            $result->putLog(esc_html__("Extract archive failed.", "twitter-archive-importer"));
            return $result;
        }

        // 必要なファイルがあるか確認する
        $necessaryFiles = ["data/account.js", "data/tweet-headers.js"];
        foreach ($necessaryFiles as $file) {
            if (!$wp_filesystem->exists($extract_to . $file)) {
                $result->putLog(sprintf(__(
                    /* translators: %s replaces to file name. */
                    "Necessary file \"%s\" is not found. Please check it is the correct archive file.",
                    "twitter-archive-importer"
                ), $file));
                return $result;
            }
        }

        // 不要ファイルを削除する
        if (!deleteUnusedFile($extract_to)) {
            $result->putLog(esc_html__("Delete of unused files failed.", "twitter-archive-importer"));
            return $result;
        }

        // account.jsを読み込む
        $accountJs = $wp_filesystem->get_contents($extract_to . "data/account.js");
        if (false === $accountJs) {
            $result->putLog(esc_html__("Loading account data (accounts.js) failed.", "twitter-archive-importer"));
            return $result;
        }
        // *.jsはJSONじゃないので置き換え必要…
        $accountJs = remove_js_header($accountJs);
        $account = json_decode($accountJs);
        if (is_null($account)) {
            $result->putLog(esc_html__("Loading account data (accounts.js) failed.", "twitter-archive-importer"));
            return $result;
        }
        $accountId = $account[0]->account->accountId;
        $username = $account[0]->account->username;

        // tweet-headers.jsを読み込む
        $tweetHeadersJs = $wp_filesystem->get_contents($extract_to . "data/tweet-headers.js");
        if (false === $tweetHeadersJs) {
            $result->putLog(esc_html(sprintf(__(
                /* translators: %s replaces to file name */
                "Loading tweet data (%s) failed.",
                "twitter-archive-importer"
            ), "tweet-headers.js")));
            return $result;
        }
        $tweetHeadersJs = remove_js_header($tweetHeadersJs);
        $tweetHeaders = json_decode($tweetHeadersJs);
        if (is_null($tweetHeaders)) {
            $result->putLog(esc_html(sprintf(__(
                /* translators: %s replaces to file name */
                "Loading tweet data (%s) failed.",
                "twitter-archive-importer"
            ), "tweet-headers.js")));
            return $result;
        }
        // partファイルの読み込み
        $data_file_list = $wp_filesystem->dirlist($extract_to . "data/");
        if (false === $data_file_list) {
            $result->putLog(esc_html__("Loading data directory failed.", "twitter-archive-importer"));
            return $result;
        }
        $part_file_list = array_filter($data_file_list, function ($file_name) {
            return fnmatch("tweet-headers-part*.js", $file_name);
        }, ARRAY_FILTER_USE_KEY);
        foreach ($part_file_list as $file) {
            $js = $wp_filesystem->get_contents($extract_to . "data/" . $file["name"]);
            if (false === $js) {
                $result->putLog(esc_html(sprintf(__(
                    /* translators: %s replaces to file name */
                    "Loading tweet data (%s) failed.",
                    "twitter-archive-importer"
                ), $file["name"])));
                return $result;
            }
            $js = remove_js_header($js);
            $data = json_decode($js);
            if (is_null($data)) {
                $result->putLog(esc_html(sprintf(__(
                    /* translators: %s replaces to file name */
                    "Loading tweet data (%s) failed.",
                    "twitter-archive-importer"
                ), $file["name"])));
                return $result;
            }
            $tweetHeaders = array_merge($tweetHeaders, $data);
        }

        // データを整える
        {
            $tmp = [];
            foreach ($tweetHeaders as $tweet) {
                array_push($tmp, $tweet->tweet);
            }
            $tweetHeaders = $tmp;
        }

        // 日時を扱いやすい形に変換する。ついでに扱いやすい形にして日付データも持たせる
        array_map(function ($tweet) {
            $tweet->dt = new DateTimeImmutable($tweet->created_at);
            $tweet->date = DateTime::createFromImmutable($tweet->dt)->setTimezone(wp_timezone())->format("Y-m-d");
            $tweet->id = intval($tweet->tweet_id);
        }, $tweetHeaders);

        // 処理していないツイートだけに絞る
        $processedId = $twitterArchiveImporter->getProcessedId($accountId);
        $tweetHeaders = array_filter($tweetHeaders, function ($tweet) use ($processedId) {
            return $tweet->id > $processedId;
        });

        // ソートする。日時古い物から、ID小さいものから
        usort($tweetHeaders, function ($a, $b) {
            if ($a->dt == $b->dt) {
                return $b->id - $a->id;
            }
            return $a->dt < $b->dt ? -1 : 1;
        });

        // 日付でグループ化
        $tweetsByDate = [];
        foreach ($tweetHeaders as $tweet) {
            $date = $tweet->date;
            if (!array_key_exists($date, $tweetsByDate)) {
                $tweetsByDate[$date] = [];
            }
            array_push($tweetsByDate[$date], $tweet);
        }

        // 日付別処理
        foreach ($tweetsByDate as $date => $tweets) {
            $dateTime = DateTimeImmutable::createFromFormat("Y-m-d", $date, wp_timezone());
            $postId = $twitterArchiveImporter->getPostId($accountId, $date);
            $currentBody = "";
            if (!is_null($postId)) {
                // 投稿IDの指定がある場合は、投稿の取得を試みる
                $post = get_post($postId);
                if (is_null($post)) {
                    // 投稿の取得が出来なかった場合は投稿ID=null（投稿なし）とする
                    $postId = null;
                } else {
                    $currentBody = $post->post_content;
                }
            }

            $hasError = false;
            $omitScript = false; // 1回目のみスクリプトを取得する
            $maxTweetId = 0; // 処理済みツイートID最大値
            // ツイート別の処理を行う
            $urlList = [];
            foreach ($tweets as $tweet) {
                $tweetUrl = "https://twitter.com/" . $username . "/status/" . $tweet->id;
                $oembedUrl = oEmbedEndpoint . "?url=" . urlencode($tweetUrl);
                if ($omitScript) {
                    $oembedUrl .= "&omit_script=true";
                }
                array_push($urlList, $oembedUrl);
                $maxTweetId = $tweet->id;
                $omitScript = true;
            }

            $oembeds = getMultiContents($urlList);
            foreach ($oembeds as $oembedRes) {
                if ($oembedRes["http_code"] !== 200) {
                    // エラー発生時はここで処理終了
                    $hasError = true;
                    break;
                }

                // JSON変換
                $oembed = json_decode($oembedRes["content"]);
                if (is_null($oembed)) {
                    // エラー発生時はここで処理終了
                    $hasError = true;
                    break;
                }
                $html = $oembed->html;
                $currentBody .= $html;
            }

            if ($hasError) {
                $result->putLog(esc_html(sprintf(
                    __(
                        /* translators: %s replaced target date */
                        "Create post of %s failed. (Get tweet)",
                        "twitter-archive-importer"
                    ),
                    $dateTime->format(get_option("date_format"))
                )));
                return $result;
            }

            $postParams = [];
            $postParams["post_content"] = $currentBody;
            if (is_null($postId)) {
                $postParams["post_date"] = $dateTime->format("Y-m-d H:i:s");
                $postTitle = sprintf($twitterArchiveImporter->getPostTitleTemplate(), $username, $dateTime->format(get_option("date_format")));
                $postParams["post_title"] = $postTitle;
                $postParams["post_name"] = $username . "-twitter-log-" . $dateTime->format("Y-m-d");
                $postParams["post_category"] = [$twitterArchiveImporter->getCategoryId()];
                $postParams["post_status"] = "publish";
                $postId = wp_insert_post($postParams, true);
            } else {
                $postParams["ID"] = $postId;
                $postId = wp_update_post($postParams, true);
            }

            if (is_wp_error($postId)) {
                $result->putLog(esc_html(sprintf(
                    __(
                        /* translators: %s replaced target date */
                        "Create post of %s failed. (Post)",
                        "twitter-archive-importer"
                    ),
                    $dateTime->format(get_option("date_format"))
                )));
                return $result;
            }

            $result->putLog(esc_html(sprintf(
                __(
                    /* translators: %s replaced target date */
                    "Create post of %s successed.",
                    "twitter-archive-importer"
                ),
                $dateTime->format(get_option("date_format"))
            ))
                . " " . sprintf("<a href=\"%s\" target=\"_blank\">", get_post_permalink($postId))
                . esc_html__("check post", "twitter-archive-importer")
                . "</a>");

            $twitterArchiveImporter->setPostId($accountId, $date, $postId);
            $twitterArchiveImporter->setProcessedId($accountId, $maxTweetId);
        }
    } finally {
        $wp_filesystem->rmdir($extract_to, true);
    }
    $result->success();
    return $result;
}

/**
 * 不要なファイルを削除する
 * @param string $extract_to 展開先パス
 * @return bool 成否
 */
function deleteUnusedFile($extract_to): bool
{
    /**
     * @var WP_Filesystem_Base $wp_filesystem
     */
    global $wp_filesystem;

    // 不要ファイルを削除する
    if (
        !$wp_filesystem->delete($extract_to . "Your Archive.html") ||
        !$wp_filesystem->delete($extract_to . "assets", true)
    ) {
        return false;
    }
    $delete_data_dirs = [
        "community_tweet_media", "deleted_tweets_media", "direct_messages_group_media", "direct_messages_media", "moments_media", "moments_tweets_media", "profile_media", "tweets_media", "twitter_article_media", "twitter_circle_tweet_media"
    ];
    foreach ($delete_data_dirs as $dir) {
        if (!$wp_filesystem->delete($extract_to . "data/" . $dir, true)) {
            return false;
        }
    }

    // 必要なファイル以外を削除する
    $dataDir = $extract_to . "data/";
    $using_files = [
        "account.js", // アカウント情報
        "tweet-headers.js", // ID & Date
        "tweet-headers-part*.js",
    ];
    $data_file_list = $wp_filesystem->dirlist($dataDir);
    if (false === $data_file_list) {
        return false;
    }
    foreach ($data_file_list as $file) {
        $delete = true;
        foreach ($using_files as $pattern) {
            if (fnmatch($pattern, $file["name"])) {
                $delete = false;
                break;
            }
        }
        if ($delete) {
            if (!$wp_filesystem->delete($dataDir . $file["name"])) {
                return false;
            }
        }
    }
    return true;
}

/**
 * HTTPアクセスの並列実行
 * @param array $url_list URLのリスト
 * @return array urlをキーとする配列。レスポンスはcontentに入っている
 * @see https://techblog.yahoo.co.jp/architecture/api1_curl_multi/
 */
function getMultiContents($url_list): array
{
    $results = [];
    $mh = curl_multi_init();
    $ch_list = [];

    foreach ($url_list as $url) {
        $ch_list[$url] = curl_init($url);
        curl_setopt($ch_list[$url], CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch_list[$url], CURLOPT_TIMEOUT, 10);
        curl_multi_add_handle($mh, $ch_list[$url]);
    }

    $running = null;
    do {
        curl_multi_exec($mh, $running);
    } while ($running);

    foreach ($url_list as $url) {
        $results[$url] = curl_getinfo($ch_list[$url]);
        $results[$url]["content"] = curl_multi_getcontent($ch_list[$url]);
        curl_multi_remove_handle($mh, $ch_list[$url]);
        curl_close($ch_list[$url]);
    }

    curl_multi_close($mh);
    return $results;
}
