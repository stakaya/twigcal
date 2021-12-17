<?php
    if ($request->get('mail') == '') {
        return;
    }

    $result->add('mail', $request->get('mail'));

    // メールアドレスの正規表現
    $pattern = '/^[-!#$%&\'*+\.\/0-9A-Z^_`a-z{|}~]+@'   // アカウント
             . '[-!#$%&\'*+\/0-9=?A-Z^_`a-z{|}~]+\.'    // サブドメイン
             . '[-!#$%&\'*+\.\/0-9=?A-Z^_`a-z{|}~]+$/'; // ドメイン

    // メールアドレスチェック
    if (!preg_match($pattern, $request->get('mail'))) {
        return;
    }

    $temp = $database->users->find("mail = '" . $request->get('mail') . "'", 1);
    if (count($temp) == 1) {
        $body = MAIL_BODY;
        $url  = ENV_ROOT_URL
              . 'cgi/authmail?token='
              . urlencode(encode(SYSTEM_KEY, $temp[0]['id'] . '|' . $temp[0]['created']));
        $body = str_replace('[URL]', $url, $body);
        $body = str_replace('[MAIL]', $request->get('mail'), $body);

        $logger->info($temp[0]['id'] . '|' . $temp[0]['created']);
        $logger->info($url);
        $logger->info($body);

        Mailer::send(MAIL_SEND_ADDRESS, $request->get('mail'), MAIL_SUBJECT, $body, MAIL_SEND_NAME);
        $dispatch = 'mailcheck';
        return;
    }

    require_once 'HTTP/OAuth/Consumer.php';
    $consumer = new HTTP_OAuth_Consumer(CONSUMER_KEY, CONSUMER_SECRET);
    $http_request = new HTTP_Request2();
    $http_request->setConfig('ssl_verify_peer', false);
    $consumer_request = new HTTP_OAuth_Consumer_Request;
    $consumer_request->accept($http_request);
    $consumer->accept($consumer_request);

    $consumer->getRequestToken('https://twitter.com/oauth/request_token', CALL_BACK_URL);
    $session->add('request_token', $consumer->getToken());
    $session->add('request_token_secret', $consumer->getTokenSecret());
    $session->add('mail', $request->get('mail'));
    header('Location: ' . $consumer->getAuthorizeUrl('https://twitter.com/oauth/authorize'));
?>
