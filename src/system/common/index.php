<?php
    $path = dirname(dirname(__FILE__));

    // 基本クラス読み込み
    require_once($path . '/common/util.php');
    require_once($path . '/common/Request.php');
    require_once($path . '/common/Session.php');
    require_once($path . '/common/Result.php');
    require_once($path . '/common/define.php');
    require_once($path . '/common/PdoDAO.php');
    require_once($path . '/common/Template.php');
    require_once($path . '/common/LogAdapter.php');
    require_once($path . '/common/Mailer.php');

    // デバック時以外はエラー出力しない。
    if (RUN_DEBUG == 'ON') {
        ini_set('display_errors', '1');
    } else {
        ini_set('display_errors', '0');
    }

    // 暗黙オブジェクト作成
    $result   =& new Result();
    $request  =& new Request();
    $session  =& new Session();
    $database =& new PdoDAO();

    $action = $request->get('action');

    try {
        // データベース用クラス読み込み
        foreach (glob($path . '/model/*.php') as $temp) {
            include_once($temp);
            $temp = explode('/', $temp);
            $temp = str_replace('.php', '', $temp[count($temp) -1]);
            $method = strtolower($temp);
            $database->$method =& new $temp();
        }

        // 動作がない場合
        if ($action == '') {
            include_once($path . '/common/404.html');
        }

        // ログスタート
        $logger = new LogAdapter($action);

        // デバッグ用ログ
        $logger->debug("Request\n" . print_r($request->getAll(), true));

        // アクション
        if (file_exists('action/' . $action . '.php')) {
            include_once('action/' . $action . '.php');
        } else {
            include_once($path . '/common/404.html');
        }

        if (isset($dispatch)) {
            $action = $dispatch;
        }

        // テンプレート展開
        $template = new Template('view/' . $action . '.html');
        $template->execute($result->getAll());

        // デバッグ用ログ
        $logger->debug("Result\n". print_r($result->getAll(), true));

        // デバッグ用ログ
        $logger->debug("Print\n" . $result->buffer);

        // ブラウザへそのまま出力
        $result->output();
    } catch (Exception $e) {
        $logger->error($e->getMessage());
    }
?>
