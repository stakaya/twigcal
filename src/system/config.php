<?php
// デバック(ON|OFF)
define('RUN_DEBUG', 'ON');

// DB関連
define('DB_USER', '');
define('DB_PASS', '');
define('DB_CODE', 'UTF-8');

// 暗号化キー
define('SYSTEM_KEY', '');

// ツイッターキー
define('CONSUMER_KEY', '');
define('CONSUMER_SECRET', '');

// 環境によって変更する項目
if (PHP_OS == 'WIN32' || PHP_OS == 'WINNT') {

    // メール
    define('MAIL_SMTP_HOST', '');
    define('MAIL_SMTP_USER', '');
    define('MAIL_SMTP_PASS', '');
    define('MAIL_SMTP_AUHT', true);

} else {

    // LOG
    define('ENV_LOG_PATH', '/tmp/');
}

// Twitterコールバック
define('CALL_BACK_URL', 'http://localhost/twigcal/cgi/fromtwitter');
define('MAIL_SEND_ADDRESS', '');
define('MAIL_SEND_NAME', '');
define('MAIL_SUBJECT', '');

$temp=<<<EOD
[MAIL] のメールを確認するリクエストをいただきました。

このアドレスからのメールを自動投稿する場合は、下記のリンクをクリックしてリクエストを承認してください。

[URL]

リンクをクリックしても機能しない場合は、ブラウザで新しいウィンドウを開き、このリンクの URL
を貼り付けてください。

TwiGcal をご利用いただき、ありがとうございます。

このリクエストを承認しない場合は、本メールを無視してください。
上記のリンクをクリックしてリクエストを承認しない限り、[MAIL] からご使用の
Twitterアカウントへ自動投稿することはできません。

本メールは自動メールです。返信なさらぬようお願いいたします。
TwiGcal へのお問い合わせは、下記のメールアドレスへお願いします。

EOD;
define('MAIL_BODY' ,$temp);

?>
