<?php
require_once 'vendor/autoload.php';

$client = new Google_Client();
$client->setClientId('26117127430-lidvqh2ruq7ebmoat050pl6aqtd0ehs4.apps.googleusercontent.com');
$client->setClientSecret('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');
$client->setRedirectUri('http://localhost/LoginAct/google-callback.php');
$client->addScope('email');
$client->addScope('profile');

$login_url = $client->createAuthUrl();
header('Location: ' . filter_var($login_url, FILTER_SANITIZE_URL));
exit;
?>
