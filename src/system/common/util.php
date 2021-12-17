<?php
    /**
     * ファイル出力
     * @param     String    $filename    ファイル名
     * @param     String    $value    文字列
     */
    function fout($filename, $value)
    {
        // ファイルを開く
        $temp = @fopen($filename, 'w');

        // セッション上の番号と一致する場合
        if ($temp) {
            flock($temp, LOCK_EX);
            fputs($temp, $value);
            flock($temp, LOCK_UN);
            fclose($temp);
        }
    }

    /**
     * 'を''に置き換える
     * @param     String    $value    文字列
     * @return    String    変換後の文字列
     */
    function inSql($value)
    {
        if (is_numeric($value)) {
            return $value;
        }

        return str_replace("'", "''", $value);
    }

    /**
     * 改行を空白に置き換える
     * @param     String    $data    文字列
     * @return    String    変換後の文字列
     */
    function trimCrLf($data) {
        return str_replace(array("\r\n", "\n", "\r"), '', $data);
    }

    /**
     * デバッグ用
     * @param     String    $data 配列データ
     * @return    String    表示データ
     */
    function p($data) {
        if (PHP_OS == 'WIN32' || PHP_OS == 'WINNT') {
            print mb_convert_encoding(print_r($data, true), 'sjis-win', mb_internal_encoding());
        } else {
            print mb_convert_encoding(print_r($data, true), 'eucJP-win', mb_internal_encoding());
        }
    }

    /**
     * RSS用の日付を表示
     * @param     String    $data 日付データ
     * @param     String    $zone タイムゾーン
     * @return    String    日付データ
     */
    function pubDate($data, $zone = '+0000') {
        return gmdate('D, d M Y H:i:s', strtotime($data)) . ' ' . $zone;
    }

    /**
     * サムネイル画像を作成する
     * @param  String $jpgpath パス＋ファイル名
     * @param  String $jpgout  出力ファイル名
     * @param  Number $width   幅
     * @param  Number $height  高さ
     * @return boolean 実行結果
     */
    function createThumbNail($jpgpath, $jpgout, $width = 180 ,$height = 180) {

        if (file_exists($jpgout)) {
            return true;
        }

        if (!file_exists($jpgpath)) {
            return false;
        }

        // サムネイル用JPEGファイル作成
        $in = imageCreateFromJpeg($jpgpath);

        // 画像の幅と高さを取得
        $size = getImageSize($jpgpath);
        $imageWidth  = $width;
        $imageHeight = $height;

        // 横長の場合
        if ($size[0] * $height < $size[1] * $width) {
           $imageWidth  = $size[0]  * $height / $size[1];
        }

        // 縦長の場合
        if ($size[0] * $height > $size[1] * $width) {
           $imageHeight = $size[1] * $width / $size[0];
        }

        // サムネイルの作成
        $out = imageCreateTrueColor($imageWidth, $imageHeight);

        // 元画像をコピー(imagecopyresampled:高品質、imageCopyResized:低品質)
        if (function_exists('imagecopyresampled')) {
            imagecopyresampled($out, $in, 0, 0, 0, 0, $imageWidth, $imageHeight, $size[0], $size[1]);
        } else {
            imageCopyResized($out, $in, 0, 0, 0, 0, $imageWidth, $imageHeight, $size[0], $size[1]);
        }

        // サムネイル画像を作成
        imageJpeg($out, $jpgout);

        // 作成したイメージを削除
        imageDestroy($in);
        imageDestroy($out);

        return true;
    }

    /**
     * イメージを回転させる
     * @param     String    $infile   ファイル
     * @param     String    $outfile  ファイル
     * @param     String    $position 角度
     */
    function turnImage($infile, $outfile, $position) {

        // ポジション指定無し
        if ($position == '') {
            return;
        }

        if ($infile <> '') {
            // イメージ回転
            $source = @imagerotate(imagecreatefromjpeg($infile), $position, 0);
        }

        // イメージ出力
        if ($source && $outfile <> '') {
            imagejpeg($source, $outfile);
        }
    }

    /**
     * エンコーダ
     * @param     String    $key    暗号キー
     * @param     String    $text   プレーンテキスト
     * @return    String    暗号テキスト
     */
    function encode($key, $text) {

        if ($text == '') {
            return '';
        }

        if (!function_exists('mcrypt_module_open')) {
            return base64_encode($text);
        }

        // モジュールをオープンし、IV を作成します
        $td   = mcrypt_module_open('des', '', 'ecb', '');
        $key  = substr($key, 0, mcrypt_enc_get_key_size($td));
        $size = mcrypt_enc_get_iv_size($td);
        $iv   = mcrypt_create_iv($size, MCRYPT_RAND);

        // 暗号化ハンドルを初期化します
        if (mcrypt_generic_init($td, $key, $iv) != -1) {

            // データを暗号化します
            $temp = mcrypt_generic($td, $text);

            // 後始末をします
            mcrypt_generic_deinit($td);
            mcrypt_module_close($td);
            return base64_encode($temp);
        }

        // 暗号化できない為、空文字返却
        return '';
    }

    /**
     * デコーダ
     * @param     String    $key    暗号キー
     * @param     String    $text   暗合テキスト
     * @return    String    復号テキスト
     */
    function decode($key, $text) {

        if ($text == '') {
            return '';
        }

        if (!function_exists('mcrypt_module_open')) {
            return base64_decode($text);
        }

        // モジュールをオープンし、IV を作成します
        $td   = mcrypt_module_open('des', '', 'ecb', '');
        $key  = substr($key, 0, mcrypt_enc_get_key_size($td));
        $size = mcrypt_enc_get_iv_size($td);
        $iv   = mcrypt_create_iv($size, MCRYPT_RAND);

        // 復号化ハンドルを初期化します
        if (mcrypt_generic_init($td, $key, $iv) != -1) {

            // データを復号化します
            $temp = mdecrypt_generic($td, base64_decode($text));

            // 後始末をします
            mcrypt_generic_deinit($td);
            mcrypt_module_close($td);
            return rtrim($temp, "\0");
        }

        // 復号化できない為、空文字返却
        return '';
    }

    /**
     * wgetコマンドの関数版
     * @param     String    $url    URL
     * @param     String    $file   ファイル名
     * @param     String    $port ポート
     * @param     String    $timeout タイムアウト
     * @return    String    取得したファイル名
     */
    function wget($url, $file, $port = 80, $timeout = 10) {

        $temp = http($url, $file, $port, $timeout);
        if (!$temp) {
            return false;
        }

        $tempfile = tempnam(sys_get_temp_dir(), 'php');
        if (($fp = @fopen($tempfile, 'wb')) !== false) {
            flock($fp, LOCK_EX);
            fputs($fp, $temp);
            flock($fp, LOCK_UN);
            fclose($fp);
            return $tempfile;
        }
        return false;
    }

    /**
     * HTTPアクセス
     * @param     String    $url    URL
     * @param     String    $file   ファイル名
     * @param     String    $port ポート
     * @param     String    $timeout タイムアウト
     * @return    String    取得したデータ
     */
    function http($url, $file, $port = 80, $timeout = 10) {

        $fp = @fsockopen(basename($url), $port, $errno, $errstr, $timeout);
        if (!$fp) {
            return false;
        } else {
            $req  = "GET /$file HTTP/1.0\r\n";
            $req .= "Host: " . basename($url) . "\r\n";
            $req .= "Connection: Close\r\n\r\n";

            fwrite($fp, $req);
            $temp = '';
            while (!feof($fp)) {
                $temp .= fgets($fp, 1024);
            }
            fclose($fp);

            // HTTPヘッダを取り除く
            return substr($temp, strpos($temp, "\r\n\r\n") +4);
        }
    }

    /**
     * ページ出力
     * @param     String    $head   ヘッダ
     * @param     String    $foot   フッダ
     * @param     String    $page   ページ
     * @param     String    $maxpage   ページ最大値
     * @param     String    $back   戻るボタン
     * @param     String    $forward   進むボタン
     * @param     String    $color   ページ色
     * @return    String    ページ用HTML
     */
    function pager($head, $foot, $page, $maxpage, $perpage = 5, $back = '[&lt;]', $forward = '[&gt;]', $color = 'red') {

        // ページが複数ある場合
        if ($page > 1) {
            $pagebefore = '<a href="' . $head . ($page -1) . $foot . '">' . "$back</a>";
        } else {
            $pagebefore = '';
        }

        // ページがまだある場合
        if ($page < $maxpage) {
            $pageafter =  '<a href="' . $head . ($page +1) . $foot . '">' . "$forward</a>";
        } else {
            $pageafter = '';
        }

        // ページが少ない場合
        $pageview = '';
        if ($maxpage < $perpage) {

            // すべてのページ作成
            for ($i = 0; $i < $maxpage; $i++) {
                if (($i +1) <> $page) {
                    $pageview .= '&nbsp;<a href="' . $head . ($i +1)
                              . $foot . '">' . ($i +1) ."</a>";
                } else {
                    $pageview .= "&nbsp;<span style='color:$color;'>" . ($i +1) . '</span>';
                }
            }

        } elseif ($page < $perpage) {

              // 省略してページ作成
              for ($i = 0; $i < $perpage; $i++) {
                  if (($i +1) <> $page) {
                      $pageview .= '&nbsp;<a href="' . $head . ($i +1)
                                . $foot . '">' . ($i +1) .'</a>';
                  } else {
                      $pageview .= "&nbsp;<span style=color:$color;'>" . ($i +1) . '</span>';
                  }
              }

              // まだある
              if ($page + $perpage < $maxpage) {
                  $pageview .= '…<a href="' . $head . $maxpage
                            . $foot . '">'. "$maxpage</a>";
              }
        } else {
            // 省略してページ作成
            for ($i = $page - 3; $i < $maxpage && $i < $page + 2; $i++) {

                if ($i < 0) continue;

                if (($i +1) <> $page) {
                    $pageview .= '&nbsp;<a href="' . $head . ($i +1)
                              . $foot . '">' . ($i +1) .'</a>';
                } else {
                    $pageview .= "&nbsp;<span style=color:$color;'>" . ($i +1) . '</span>';
                }
            }

            // まだある
            if ($page + 2 < $maxpage) {
                $pageview .= '…<a href="' . $head . $maxpage
                          . $foot . '">'. "$maxpage</a>";
            }
        }

        // ページが複数ある場合
        if (1 < $maxpage) {
            return '<div style="text-align:center;font-size:xx-small;">'
                 . "$pagebefore$pageview&nbsp;$pageafter"
                 . '</div>';
        }

        return '';
    }

    /**
     * 配列内のデータを指定キーにてマージする
     * @param     Array    $data  マージされていない配列データ
     * @param     String   $key   キー名
     * @return    Array    マージしたデータ
     */
    function merge($data, $key) {
        $work = Array();
        $result = Array();
        $i = 0;

        // キーをマージ
        foreach ($data as $temp) {
            $work[$temp[$key]] = $temp;
        }

        // 配列に戻す
        foreach ($work as $temp) {
            $result[$i++] = $temp;
        }
        return $result;
    }


    /**
     * 指定キーの値で連想配列へ変換する
     * @param     Array    $data  配列データ
     * @param     String   $id    Keyデータ
     * @return    Array    連想配列データ
     */
    function stripindex($data, $id) {
        $result = Array();

        // キーをマージ
        foreach ($data as $temp) {
            $result[$temp[$id]] = $temp;
        }

        return $result;
    }

    /**
     * CSVのデータをダウンロードさせる
     * @param     Array    $data  配列データ
     * @param     String   $filename ファイル名
     * @param     String   $col   セパレータ
     * @param     String   $row   改行
     * @return    String   区切りデータ
     */
    function csvDownLoad($data, $filename = 'data.csv', $col = ',', $row = "\n") {
        header('Content-Type: text/comma-separated-values');
        header('Content-Disposition: attachment; filename=' . $filename);

        foreach ($data as $value) {
            print(implode($col, $value) . $row);
        }
    }
?>
