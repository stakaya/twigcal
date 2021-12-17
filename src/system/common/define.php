<?php
    $systempath = dirname(dirname(dirname(__FILE__)));
    require_once($systempath . DIRECTORY_SEPARATOR
                             . 'system'
                             . DIRECTORY_SEPARATOR
                             . 'config.php');
    // 文字コード
    mb_internal_encoding('UTF-8');

    // 言語
    mb_language('Japanese');

    // タイムゾーン
    date_default_timezone_set('Asia/Tokyo');

    // システム情報
    define('SYSTEM_VERSION', '0.1');

    // DBユーザ
    if (!defined('DB_USER')) {
        define('DB_USER', '');
    }

    // DBパスワード
    if (!defined('DB_PASS')) {
        define('DB_PASS', '');
    }

    // DBコード
    if (!defined('DB_CODE')) {
        define('DB_CODE', 'UTF-8');
    }

    // マスタDB情報
    if (!defined('DB_HOST')) {
        define('DB_HOST', 'sqlite:' . $systempath
                                    . DIRECTORY_SEPARATOR
                                    . 'system'
                                    . DIRECTORY_SEPARATOR
                                    . 'database'
                                    . DIRECTORY_SEPARATOR
                                    . 'data.sqlite');
    }

    // LOG情報
    if (!defined('ENV_LOG_PATH')) {
        define('ENV_LOG_PATH', $systempath . DIRECTORY_SEPARATOR
                                           . 'system'
                                           . DIRECTORY_SEPARATOR
                                           . 'log'
                                           . DIRECTORY_SEPARATOR);
    }

    // パス情報
    if (!defined('ENV_DOC_PATH')) {
        define('ENV_DOC_PATH', $systempath . DIRECTORY_SEPARATOR);
    }

    // URL情報
    if (!defined('ENV_ROOT_URL') && !isset($argv)) {
        define('ENV_ROOT_URL', dirname('http://' . $_SERVER['SERVER_NAME']
                                                 . dirname($_SERVER['PHP_SELF']))
                                                 . '/');
    }
?>
