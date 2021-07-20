<?php

require_once __DIR__ . '/XBLive.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$client_id = 'Client_id';

$scope = ['XboxLive.signin', 'XboxLive.offline_access'];
$state = random_int(1, 200000);
$provider = new XBLive([
    'client_id'          => $client_id,
    'redirect_uri'       => 'http://localhost/test/mic/index.php',
    'state'             => $state,
    'scope'             => $scope
]);
if (isset($_REQUEST['code']) && isset($_REQUEST['state'])) {
    if ($_REQUEST['state'] == $_SESSION['state']) {
        $code = $_REQUEST['code'];

        $msaToken = $provider->GetAccessToken(['scope' => $scope, 'code' => $_REQUEST['code']] );
        if(!$msaToken){
            echo 'Error while getting the msaToken. Please try again';
        }
        $xasuToken = $provider->getXasuToken($msaToken);
        if(!$xasuToken){
            echo 'Error while getting the xasuToken. Please try again';
        }
        $xstsToken = $provider->getXstsToken($xasuToken);
        if(!$xstsToken){
            echo 'Error while getting the xstsToken. Please try again';
        }
        $profile = $provider->getLoggedUserProfile($xstsToken);
        print_r($profile);

    } else {
        echo 'Invalid state';
    }
} else {
    // echo $provider->getBaseAuthorizationUrl();
    echo '<a href="' . $provider->getBaseAuthorizationUrl() . '"> Xbox Login </a>';
    $_SESSION['state'] = $provider->getState();
}
