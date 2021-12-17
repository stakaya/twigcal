<?php
if (!defined('RUN_DEBUG')) {
    define('RUN_DEBUG', 'ON');
}

if (!defined('ENV_LOG_PATH')) {
    define('ENV_LOG_PATH', dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR);
}

/**
 * ロガークラス
 *
 * ログを管理する。
 *
 * @package jp.takaya
 * @access  public
 * @author  Shuichi Takaya <takayashuichi@hotmail.com>
 * @create  2006/08/01
 * @version $Id:$
 **/
class LogAdapter {

    /**
     * <code>logger</code> ロガー
     */
    var $logger = null;

    /**
     * コンストラクタ
     * @param $className クラス・メソッド名称
     */
    function __construct($className) {
        $this->logger = $className;
    }

    /**
     * デバッグ用ログ出力
     * @param logInfo デバック用ログ情報
     */
    final public function debug($logInfo) {
        if (RUN_DEBUG == 'ON') {
            error_log(date('Y-m-d H:i:s') . ' [DEBUG] '
                      . $this->logger . ' ' . $logInfo . "\n",
                      3, ENV_LOG_PATH . date('Ymd') . '.log');
        }
    }

    /**
     * 情報用ログ出力
     * @param logInfo 情報用ログ情報
     */
    final public function info($logInfo) {
        error_log(date('Y-m-d H:i:s') . ' [INFO] '
                  . $this->logger . ' ' . $logInfo . "\n",
                  3, ENV_LOG_PATH . date('Ymd') . '.log');
    }

    /**
     * ワーニングログ出力
     * @param logInfo ワーニングログ情報
     */
    final public function warn($logInfo) {
        error_log(date('Y-m-d H:i:s') . ' [WARN] '
                  . $this->logger . ' ' . $logInfo . "\n",
                  3, ENV_LOG_PATH . date('Ymd') . '.log');
    }

    /**
     * エラーログ出力
     * @param logInfo エラーログ情報
     */
    final public function error($logInfo) {
        if (isset($php_errormsg)) {
            error_log(date('Y-m-d H:i:s') . ' [ERROR] '
                      . $this->logger . ' ' . $php_errormsg . "\n",
                      3, ENV_LOG_PATH . date('Ymd') . '.log');
        }
        error_log(date('Y-m-d H:i:s') . ' [ERROR] '
                  . $this->logger . ' ' . $logInfo . "\n",
                  3, ENV_LOG_PATH . date('Ymd') . '.log');
    }

    /**
     * 障害ログ出力
     * @param logInfo 障害ログ情報
     */
    final public function fatal($logInfo) {
        if (isset($php_errormsg)) {
            error_log(date('Y-m-d H:i:s') . ' [FATAL] '
                      . $this->logger . ' ' . $php_errormsg . "\n",
                      3, ENV_LOG_PATH . date('Ymd') . '.log');
        }
        error_log(date('Y-m-d H:i:s') . ' [FATAL] '
                  . $this->logger . ' ' . $logInfo . "\n",
                  3, ENV_LOG_PATH . date('Ymd') . '.log');

        // 障害情報をメールで知らせる
        if (defined('MAIL_REPORT')) {
            error_log(date('Y-m-d H:i:s') . ' ' . $logInfo, 1, MAIL_REPORT);
        }
    }
}
?>
