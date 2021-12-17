<?php
/**
 * リクエストクラス
 *
 * GET、POSTリクエストを管理する。
 *
 * @package jp.takaya
 * @access  public
 * @author  Shuichi Takaya <takayashuichi@hotmail.com>
 * @create  2005/12/01
 * @version $Id:$
 **/
class Request {

    /**
     * リクエストパラメータ
     * @var Array
     * @access public
     */
    var $request = Array();

    /**
     * リクエストクラスコンストラクタ
     */
    function Request() {

        // Arrayオブジェクトか調べる
        if (is_array($_REQUEST) ) {

            // リクエストオブジェクトを全て取り込む
            foreach($_REQUEST as $name => $value) {
                $this->add($name, $value);
            }
        }
    }

    /**
     * リクエスト情報をメンバへ追加します。
     * @access     public
     * @param     String    $name    リクエスト名称
     * @param     String    $data    リクエストデータ
     */
    function add($name, $data) {
        $this->request[$name] = $data;
    }

    /**
     * 指定されたNameのリクエストを取得して返します。
     * @access    public
     * @param     String    $name    リクエスト名称
     * @return    String    リクエスト情報
     */
    function get($name) {
        if (isset($this->request[$name])) {
            return $this->request[$name];
        }
        return null;
    }

    /**
     * リクエストを全て返します。
     * @access    public
     * @return    Array    リクエスト情報
     */
    function getAll() {
        return $this->request;
    }

    /**
     * リダイレクトする。
     * @access    public
     * @param     String    $url    URL
     */
    function redirect($url) {
        header('HTTP/1.1 301 Moved Permanently');
        header('Location: ' . $url);
    }
}
?>
