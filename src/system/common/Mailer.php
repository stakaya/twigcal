<?php

if (!defined('ATTACHE_PATH')) {
    define('ATTACHE_PATH', '');
}

if (!defined('MAIL_DIR')) {
    define('MAIL_DIR', '');
}

if (!defined('MAIL_PEAR_PATH')) {
    define('MAIL_PEAR_PATH', 'Mail.php');
}

/**
 * メールクラス
 *
 * メール送信を管理します。
 *
 * @package jp.takaya
 * @access  public
 * @author  Shuichi Takaya <takayashuichi@hotmail.com>
 * @create  2007/11/01
 * @version $Id:$
 **/
class Mailer {

    /**
     * <code>subject</code> サブジェクト
     */
    var $subject = '';

    /**
     * <code>from</code> 差出人メールアドレス
     */
    var $from = '';

    /**
     * <code>to</code> 受信者メールアドレス
     */
    var $to = '';

    /**
     * <code>date</code> 送信日付
     */
    var $date = '';

    /**
     * <code>data</code> メールデータ
     */
    var $data = '';

    /**
     * コンストラクタ
     */
    function Mailer() {
    }

    /**
     * メールデータパース処理
     * @access   public
     * @return   Array   メールボディ
     */
    function parseMail() {
        list($head, $body) = $this->splitForHeadAndBody($this->data);

        // 日付の抽出
        eregi("Date:[ \t]*([^\r\n]+)", $head, $temp = '');
        if (isset($temp[1])) {
            $this->date = $temp[1];
        }

        $head = ereg_replace("\r\n? ", '', $head);

        // サブジェクトの抽出
        if (eregi("\nSubject:[ \t].*\?=\n", $head, $temp)) {
            $subject = explode("\n", $temp[0]);
            foreach ($subject as $data) {

                // MIME Bデコード
                if (eregi("(.*)=\?iso-2022-jp\?B\?([^?]+)\?=(.*)", $data, $temp)) {
                    $this->subject .= base64_decode($temp[2]);
                }

                // MIME Qデコード
                if (eregi("(.*)=\?iso-2022-jp\?Q\?([^?]+)\?=(.*)", $data, $temp)) {
                    $this->subject .= quoted_printable_decode($temp[2]);
                }
            }

            $this->subject = mb_convert_encoding($this->subject, mb_internal_encoding(), 'JIS,SJIS');
        }

        // 送信者アドレスの抽出
        if (eregi("[\r\n]+From:[ \t]*([^\>\r\n]+@[^\>\r\n]+)[\>\r\n]", $head, $temp)) {
            $this->from = $this->getMailAddress($temp[1]);
        } else if ($this->from == '' && eregi("[\r\n]+Reply-To:[ \t]*([^\>\r\n]+@[^\>\r\n]+)\>", $head, $temp)) {
            $this->from = $this->getMailAddress($temp[1]);
        } else if ($this->from == '' && eregi("[\r\n]+Return-Path:[ \t]*([^\>\r\n]+@[^\>\r\n]+)\>", $head, $temp)) {
            $this->from = $this->getMailAddress($temp[1]);
        } else if ($this->from == '' && eregi("[\r\n]+From:[ \t]*([^\r\n]+)", $head, $temp)) {
            $this->from = $this->getMailAddress($temp[1]);
        } else if ($this->from == '' && eregi("[\r\n]+Reply-To:[ \t]*([^\r\n]+)", $head, $temp)) {
            $this->from = $this->getMailAddress($temp[1]);
        } else if ($this->from == '' && eregi("[\r\n]+Return-Path:[ \t]*([^\r\n]+)", $head, $temp)) {
            $this->from = $this->getMailAddress($temp[1]);
        }

        // 送信者アドレスの抽出
        if (eregi("[\r\n]+To:[ \t]*([^\>\r\n]+@[^\>\r\n]+)[\>\r\n]", $head, $temp)) {
            $this->to = $this->getMailAddress($temp[1]);
        } else if ($this->to == '' && eregi("[\r\n]+To:[ \t]*([^\r\n]+)", $head, $temp)) {
            $this->to = $this->getMailAddress($temp[1]);
        }

        // マルチパートの場合
        if (eregi("\nContent-type:.*multipart/", $head)) {
            eregi('boundary="([^"]+)"', $head, $temp);
            $body = str_replace($temp[1], urlencode($temp[1]), $body);
            $part = explode("\r\n--".urlencode($temp[1])."-?-?", $body);
        } else {
            // テキストメール
            $part[0] = $this->data;
        }

        return $part;
    }

    /**
     * メールデータを取得する
     * @access   public
     */
    function getMailData() {
        $data = array();

        // マルチパートを処理
        foreach ($this->parseMail() as $multi) {
            list($head, $body) = $this->splitForHeadAndBody($multi);
            $body = ereg_replace("\r\n\.\r\n$", '', $body);

            // コンテンツタイプが取得できない場合
            if (!eregi("Content-type: *([^;\n]+)", $head, $temp)) {
                continue;
            }

            // コンテンツタイプを格納
            list($main, $type) = explode('/', $temp[1]);

            // テキストの場合
            if (strtolower($main) == 'text') {

                // ベース64の場合
                if (eregi("Content-Transfer-Encoding:.*base64", $head)) {
                    $body = base64_decode($body);
                }

                // クオーテッドプリンタブルの場合
                if (eregi("Content-Transfer-Encoding:.*quoted-printable", $head)) {
                    $body = quoted_printable_decode($body);
                }

                $text = mb_convert_encoding($body, mb_internal_encoding(), 'JIS,SJIS');

                // HTMLの場合
                if ($type == 'html') {
                    // 全てのタグを削る
                    $text = strip_tags($text);
                } else {
                    $text = htmlspecialchars($text);
                }
            }

            // ファイル名を抽出
            if (eregi("name=\"?([^\"\n]+)\"?", $head, $temp)) {
                $filename = ereg_replace("[\t\r\n]", '', $temp[1]);
                while (eregi("(.*)=\?iso-2022-jp\?B\?([^\?]+)\?=(.*)", $filename, $temp)) {
                    $filename = $temp[1] . base64_decode($temp[2]) . $temp[3];
                    $filename = strtotime($this->date) . mb_convert_encoding($filename, mb_internal_encoding(), 'JIS,SJIS');
                }
            }

            // 返却用データを編集
            $data[count($data) + 1]['body'] = $text;
            $data[count($data)    ]['file'] = $filename;
        }
        return $data;
    }

    /**
     * メール添付ファイルをデコードして保存する
     * @access public
     */
    function saveAttacheFile() {

        // マルチパートを処理
        foreach ($this->parseMail() as $multi) {
            list($head, $body) = $this->splitForHeadAndBody($multi);
            $body = ereg_replace("\r\n\.\r\n$", '', $body);

            // コンテンツタイプが取得できない場合
            if (!eregi("Content-type: *([^;\n]+)", $head, $temp)) {
                continue;
            }

            // コンテンツタイプを格納
            $type = explode('/', $temp[1]);
            $type = $type[1];

            // ファイル名を抽出
            if (eregi("name=\"?([^\"\n]+)\"?", $head, $temp)) {
                $filename = ereg_replace("[\t\r\n]", '', $temp[1]);
                while (eregi("(.*)=\?iso-2022-jp\?B\?([^\?]+)\?=(.*)", $filename, $temp)) {
                    $filename = $temp[1] . base64_decode($temp[2]) . $temp[3];
                    $filename = mb_convert_encoding($filename, mb_internal_encoding(), 'JIS,SJIS');
                }
            }

            // 添付ファイルをデコードして保存
            if (eregi("Content-Transfer-Encoding:.*base64", $head)
            &&  eregi('gif|jpeg|png|bmp|mpeg|3gpp|asf', $type)) {
                $temp = base64_decode($body);
                $filename = strtotime($this->date) . $filename;
                $fp = fopen(ATTACHE_PATH . $filename, 'wb');
                fputs($fp, $temp);
                fclose($fp);
            }
        }
    }

    /**
     * ヘッダと本文を分割する
     * @access   public
     * @param    $data   String    メール本文のデータ
     * @return   Array   処理結果
     */
    function splitForHeadAndBody($data) {
        $temp = explode("\r\n\r\n", $data, 2);
        if (count($temp) <> 2) {
            $temp = explode("\n\n", $data, 2);
        }

        $temp[1] = ereg_replace("\r\n[\t ]+", ' ', $temp[1]);
        return $temp;
    }

    /**
     * メールアドレスを抽出する
     * @access   public
     * @param    $data    String    メールヘッダデータ
     * @return   String   メールアドレス
     */
    function getMailAddress($data) {

        // メールアドレスの正規表現
        $pattern = '[-!#$%&\'*+\.\/0-9A-Z^_`a-z{|}~]+@'   // アカウント
                 . '[-!#$%&\'*+\/0-9=?A-Z^_`a-z{|}~]+\.'  // サブドメイン
                 . '[-!#$%&\'*+\.\/0-9=?A-Z^_`a-z{|}~]+'; // ドメイン

        // メールアドレスを調べる
        if (eregi($pattern, $data, $temp)) {
            if($temp[0] == '') {
                return $data;
            } else {
                return $temp[0];
            }
        }

        return '';
    }

    /**
     * 標準入力からメールデータを受け取る
     * @access public
     * @return String 処理結果
     */
    function readMailFromStdin() {

        // 標準入力からデータ取得
        $stdin = fopen('php://stdin', 'r');

        // 最終行まで読む
        $data = '';
        while (!feof($stdin)) {
            $data .= fgets($stdin);
        }

        // クローズ
        fclose($stdin);
        return $data;
    }

    /**
     * メールディレクトリから最後のメールを取り出す
     * @access public
     * @return String メール本文
     */
    function readMailFromLastFile() {

        // ディレクトリチェック
        if (!is_dir(MAIL_DIR)) {
            return '';
        }

        // ディレクトリオープン
        $handle = @opendir(MAIL_DIR);

        // ディレクトリチェック
        if (!$handle) {
            return '';
        }

        // 最後に更新されたファイルを探す
        $lastTime = 0;
        while (($file = readdir($handle)) !== false) {

            // カレントディレクトリは除く
            if ($file == '.' || $file == '..') {
                continue;
            }

            // 最後に更新されたファイルを探す
            $fileTime = filemtime(MAIL_DIR . $file);
            if ($lastTime < $fileTime) {
                $lastTime = $fileTime;
                $lastFile = MAIL_DIR . $file;
            }
        }

        // ディレクトリクローズ
        closedir($handle);

        // ファイルオープン
        $fp = fopen($lastFile, 'r');
        if (!$fp) {
            return '';
        }

        // ファイルの内容を格納
        $data = fgets($fp, filesize($lastFile));
        fclose($fp);

        // データを返却
        return $data;
    }

    /**
     * 添付ファイル付きメールを送信する。
     * @param  String $from      送信元アドレス
     * @param  String $to        送信先アドレス
     * @param  String $subject   件名
     * @param  String $body      本文
     * @param  String $filename  ファイル名
     * @param  String $mime      マイムタイプ
     * @return String 実行結果
     */
    function sendAttachMail($from, $to, $subject, $body, $filename, $mime) {
        ($attach = file_get_contents($filename)) || die('Open Error:' . $filename);
        $filename = basename($filename);

        $boundary = "_Boundary_" . uniqid(rand(1000,9999) . '_') . "_";

        // 件名と本文のエンコード
        $subject = mb_encode_mimeheader($subject);
        $body    = mb_convert_encoding($body, 'ISO-2022-JP', 'auto');

        // 添付データのエンコード
        // 日本語のファイル名はRFC違反ですが、多くのメーラは理解します
        $filename = mb_encode_mimeheader($filename);

        // Base64に変換し76Byte分割
        $attach   = chunk_split(base64_encode($attach), 76, "\n");

        // メディアタイプ未指定の場合は汎用のタイプを指定
        if (!$mime) $mime = "application/octet-stream";

        // ヘッダー
        $header = "To: $to\n"
                . "From: $from\n"
                . "X-Mailer: PHP/" . phpversion() . "\n"
                . "MIME-Version: 1.0\n"
                . "Content-Type: Multipart/Mixed; boundary=\"$boundary\"\n"
                . "Content-Transfer-Encoding: 7bit";

        // メールボディ
        $mbody = "--$boundary\n"
               . "Content-Type: text/plain; charset=ISO-2022-JP\n"
               . "Content-Transfer-Encoding: 7bit\n"
               . "\n"
               . "$body\n"
               . "--$boundary\n"
               . "Content-Type: $mime; name=\"$filename\"\n"
               . "Content-Transfer-Encoding: base64\n"
               . "Content-Disposition: attachment; filename=\"$filename\"\n"
               . "\n"
               . "$attach\n"
               . "--$boundary--\n";
        return mail(null, $subject, $mbody, $header);
    }

    /**
     * メール送信処理
     * @param  String $from      送信元アドレス
     * @param  String $to        送信先アドレス
     * @param  String $subject   件名
     * @param  String $body      本文
     * @param  String $filename  ファイル名
     * @param  String $mime      マイムタイプ
     * @return String    遷移ページ
     */
    function sendByPear($from, $to, $subject, $body, $name) {

        // あて先不明
        if ($to == '' || $from == '') {
            return false;
        }

        // SMTP認証情報
        $param['host'    ] = MAIL_SMTP_HOST;
        $param['port'    ] = '25';
        $param['auth'    ] = MAIL_SMTP_AUHT;
        $param['username'] = MAIL_SMTP_USER;
        $param['password'] = MAIL_SMTP_PASS;

        // SMTPメールクラス
        require_once MAIL_PEAR_PATH;

        $mail = Mail::factory('smtp', $param);

        // あて先をコード変換
        $name = mb_convert_encoding($name, 'ISO-2022-JP');
        $to   = mb_convert_encoding($to,   'ISO-2022-JP');

        // ヘッダ
        $headers['To'] = $to;
        $headers['From'] = mb_encode_mimeheader($name) . '<' . $from . '>';
        $headers['Subject'] = mb_convert_encoding($subject, 'ISO-2022-JP');
        $headers['X-Mailer'] = 'PHP/' . phpversion();
        $headers['X-Body-Content-Type'] = 'charset=ISO-2022-JP';

        // 本文をコード変換
        $body = mb_convert_encoding($body, 'ISO-2022-JP');

        //メール送信
        return @$mail->send($to, $headers, $body);
    }

    /**
     * メール送信処理
     * @access public
     * @param  String $from      送信元アドレス
     * @param  String $to        送信先アドレス
     * @param  String $subject   件名
     * @param  String $body      本文
     * @param  String $filename  ファイル名
     * @param  String $mime      マイムタイプ
     * @return boolean   送信結果
     */
    function send($from, $to, $subject, $body, $name, $filename = null, $mime = null) {

        // あて先不明
        if ($to == '' || $from == '') {
            return false;
        }

        // 外部SMTPが設定されている場合
        if (defined('MAIL_SMTP_HOST')
        && defined('MAIL_SMTP_AUHT')
        && defined('MAIL_SMTP_USER')
        && defined('MAIL_SMTP_PASS')) {
            return Mailer::sendByPear($from, $to, $subject, $body, $name);
        }

        // 添付ファイルがある場合
        if ($filename <> null) {
            return Mailer::sendAttachMail($from, $to, $subject, $body, $filename, $mime);
        }

        // PHP.iniの設定でメール送信
        mb_language('Ja');
        $name = mb_convert_encoding($name, 'ISO-2022-JP');
        $from = 'From:' . mb_encode_mimeheader($name) . '<' . $from . '>';
        return @mb_send_mail($to, $subject, $body, $from);
    }
}
?>
