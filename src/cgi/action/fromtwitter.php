<?php
    if ($session->get('mail') == '') {
        return;
    }

    require_once 'HTTP/OAuth/Consumer.php';
    $consumer = new HTTP_OAuth_Consumer(CONSUMER_KEY, CONSUMER_SECRET);
    $http_request = new HTTP_Request2();
    $http_request->setConfig('ssl_verify_peer', false);
    $consumer_request = new HTTP_OAuth_Consumer_Request;
    $consumer_request->accept($http_request);
    $consumer->accept($consumer_request);

    $consumer->setToken($session->get('request_token'));
    $consumer->setTokenSecret($session->get('request_token_secret'));
    $consumer->getAccessToken('https://twitter.com/oauth/access_token', $request->get('oauth_verifier'));

    $data['mail'] = $session->get('mail');
    $data['request_token'] = $consumer->getToken();
    $data['token_secret'] = $consumer->getTokenSecret();
    $database->users->insert($data);
?>
