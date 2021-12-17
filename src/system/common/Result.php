<?php
/**
 * 処理結果クラス
 *
 * データベースのデータや計算結果を格納します。
 * モデルからビューへの情報引渡しの際使用します。
 *
 * @access  public
 * @author  Shuichi Takaya <takayashuichi@hotmail.com>
 * @create  2005/12/01
 * @version $Id:$
 **/
class Result {

    /**
     * 処理結果フィールド
     * @var Array
     * @access public
     */
    var $result = Array();

    /**
     * 処理結果バッファ
     * @var String
     * @access public
     */
    var $buffer = '';

    /**
     * 処理結果情報をメンバへ追加します。
     * @access    public
     * @param     String    $data    出力データ
     */
    function write($data) {
        $this->buffer .= $data;
    }

    /**
     * 処理結果情報をフラッシュします。
     * @access    public
     * @param     String    $data    出力データ
     */
    function output() {
        print $temp = $this->buffer;
        $this->buffer = '';
        return $temp;
    }

    /**
     * 処理結果情報をメンバへ追加します。
     * @access    public
     * @param     String    $name    処理結果名称
     * @param     String    $data    処理結果データ
     */
    function add($name, $data) {
        $this->result[$name] = $data;
    }

    /**
     * 指定されたNameの処理結果を取得して返します。
     * @access    public
     * @param     String    $name    処理結果名称
     * @return    String    処理結果情報
     */
    function get($name) {
        if (isset($this->result[$name])) {
            return $this->result[$name];
        }
        return null;
    }

    /**
     * 処理結果を全て返します。
     * @access    public
     * @return    Array    処理結果情報
     */
    function getAll() {
        return $this->result;
    }
}
?>
