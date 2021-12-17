<?php
/**
 * セッションクラス
 *
 * セッション情報を管理する。
 *
 * @package jp.takaya
 * @access  public
 * @author  Shuichi Takaya <takayashuichi@hotmail.com>
 * @create  2005/12/01
 * @version $Id:$
 **/
class Session {

    /**
     * セッションクラスコンストラクタ
     */
    function Session() {
        session_cache_limiter('private_no_expire');
        session_start();
        $_SESSION['session'] = 'on';
    }

    /**
     * 現在のセッションを削除します。
     * @access    public
     */
    function kill() {
        foreach($_SESSION as $name => $value) {
            session_unregister($name);
        }
    }

    /**
     * セッション情報を削除します。
     * @access    public
     * @param     String    $name    セッション名称
     */
    function clear($name) {
        unset($_SESSION[$name]);
    }

    /**
     * セッション情報をセッションへ追加します。
     * @access    public
     * @param     String    $name    セッション名称
     * @param     String    $data    セッションデータ
     */
    function add($name, $data) {
        $_SESSION[$name] = $data;
    }

    /**
     * 指定されたセッションを取得して返します。
     * @access    public
     * @param     String    $name    セッション名称
     * @return    String    セッション情報
     */
    function get($name) {
        if (isset($_SESSION[$name])) {
            return $_SESSION[$name];
        }
        return null;
    }

    /**
     * 指定されたセッションを全て返します。
     * @access    public
     * @return    Array    セッション情報
     */
    function getAll() {
        return $_SESSION;
    }
}
?>
