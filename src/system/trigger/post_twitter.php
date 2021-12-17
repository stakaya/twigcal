#!php -q
<?php
    $path = dirname(dirname(__FILE__));
    require_once($path . '/common/define.php');
    require_once($path . '/common/util.php');
    require_once($path . '/common/PdoDAO.php');
    require_once($path . '/common/LogAdapter.php');
    require_once($path . '/common/Mailer.php');

    // カレントの言語を日本語に設定する
    mb_language('ja');

    // 内部文字エンコードを設定する
    mb_internal_encoding('UTF-8');

    // ログ
    $logger = new LogAdapter(basename(__FILE__));

    // メーラー生成
    $mailer = new Mailer();

    // 標準入力からメールデータを取得
    $mailer->data = $mailer->readMailFromStdin();
    //$logger->info($mailer->data);

    // メールをパース
    $mailer->parseMail();
    $logger->info('from:' . $mailer->from);
    $logger->info('to:' . $mailer->to);
    $logger->info('subject:' . $mailer->subject);

    // 件名からポストするデータを切り出す
    $temp = explode(' @ 20', $mailer->subject);
    $post['status'] = '';
    if (count($temp) > 0) {
        $post['status'] = strlen($temp[0]) > 140 ? mb_substr($temp[0], 0, 140) : $temp[0];
    }

    // アドレス認証メール
    if ($mailer->from == 'mail-noreply@google.com') {
        // URLの正規表現
        $pattern = 'http(:\/\/[-_.!~*\'()a-zA-Z0-9;\/?:\@&=+\$,%#]+)';

        // メールアドレスを調べる
        if (eregi($pattern, $mailer->data, $temp)) {
            if($temp[0] <> '') {
                // メールアドレス認証
                @file_get_contents($temp[0]);
            }
        }

        return;
    } elseif ($mailer->from != 'calendar-notification@google.com') {
        return;
    }

    // データベース用クラス読み込み
    $database =& new PdoDAO();
    foreach (glob($path . '/model/*.php') as $temp) {
        include_once($temp);
        $temp = explode('/', $temp);
        $temp = str_replace('.php', '', $temp[count($temp) -1]);
        $method = strtolower($temp);
        $database->$method =& new $temp();
    }

    // Twitterにデータを投稿
    $temp = $database->users->find("mail = '" . $mailer->to . "' order by id desc", 1);
    if (count($temp) == 1) {
        if ($temp[0]['request_token'] <> '' && $temp[0]['token_secret'] <> '') {
            require_once 'HTTP/OAuth/Consumer.php';
            $consumer = new HTTP_OAuth_Consumer(CONSUMER_KEY, CONSUMER_SECRET);
            $http_request = new HTTP_Request2();
            $http_request->setConfig('ssl_verify_peer', false);
            $consumer_request = new HTTP_OAuth_Consumer_Request;
            $consumer_request->accept($http_request);
            $consumer->accept($consumer_request);
            $consumer->setToken($temp[0]['request_token']);
            $consumer->setTokenSecret($temp[0]['token_secret']);
            $response = $consumer->sendRequest('https://twitter.com/statuses/update.xml', $post, 'POST');
        }
    }
?>
