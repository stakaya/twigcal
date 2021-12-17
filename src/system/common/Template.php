<?php
/**
 * テンプレートクラス
 *
 * テンプレートエンジン。
 *
 * @package jp.takaya
 * @access  public
 * @author  Shuichi Takaya <takayashuichi@hotmail.com>
 * @create  2009/12/01
 * @version $Id:$
 **/
class Template {

    /**
     * リクエストパラメータ
     * @var String
     * @access public
     */
    var $cache = null;

    /**
     * コンストラクタ
     * テンプレートを読み込み、テンプレートの中の特殊文字を置換する。
     * '$[...]' を 'echo ...;' に置換
     * '${...}' を 'echo htmlspecialchars(...);' に置換
     * XML宣言のエラー回避
     * テンプレートを展開した後、キャッシュファイルを作成する。
     * @access public
     * @param  String $filename テンプレートファイル名称
     */
    function Template($filename) {
        if (file_exists($filename)) {
            $this->cache = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cache';
            if (!is_dir($this->cache)) mkdir($this->cache);
            $this->cache .= DIRECTORY_SEPARATOR . basename($filename) . '.cache';
            if (!file_exists($this->cache) || filemtime($this->cache) < filemtime($filename)) {
                $temp = file_get_contents($filename);
                $temp = preg_replace('/^<\?xml/', '<<?php ?>?xml', $temp);
                $temp = preg_replace('/\$\[(.*?)\]/', '<?php echo $$1; ?>', $temp);
                $temp = preg_replace('/\$\{(.*?)\}/', '<?php echo htmlspecialchars($$1); ?>', $temp);
                file_put_contents($this->cache, $temp);
            }
        }
    }

    /*
     * 変数名をテンプレートに展開する。
     * @access public
     * @param  Array  $context  変数情報を格納した配列
     */
    function execute($context) {

        // Arrayオブジェクトか調べる
        if (is_array($context)) {
            extract($context);
        }

        if ($this->cache <> null) {
            include($this->cache);
        }
    }
}
?>
