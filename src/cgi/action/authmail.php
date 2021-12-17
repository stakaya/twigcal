<?php
    if ($request->get('token') == '') {
        return;
    }

    list($id, $created) = explode('|', decode(SYSTEM_KEY, $request->get('token')));
    $where = "id = '" . $id . "' and created = '" . $created . "'";
    $temp = $database->users->find($where, 1);

    if (count($temp) == 1) {
        $database->users->delete($temp[0]['id']);
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
        $session->add('mail', $temp[0]['mail']);
        header('Location: ' . $consumer->getAuthorizeUrl('https://twitter.com/oauth/authorize'));
    }
?>
