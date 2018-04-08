<?php
global $CFG;
$username = $_POST['username'];
$password = $_POST['password'];
$service = $_POST['service'];
$state = $_POST['state'];
$clientid = $_POST['client_id'];
$redirect_uri = $_POST['redirect_uri'];

$ch = curl_init();
$values = array(
        'username' => $username,
        'password' => $password,
        'service' => $service
);
//$params = http_build_query($values);
$options = array(
        //CURLOPT_URL => $CFG->wwwroot . '/login/token.php/',
        CURLOPT_URL => 'https://alexa.haietza.com/login/token.php/',
        CURLOPT_POSTFIELDS => $values,
        CURLOPT_RETURNTRANSFER => 1
);
curl_setopt_array($ch, $options);
$data = curl_exec($ch);
curl_close($ch);

$obj = json_decode($data, true);
//echo $obj['token'];
$redirect = $redirect_uri . '#state=' . $state . '&access_token=' . $obj['token'] . '&token_type=Bearer';
header ("Location: $redirect");
?>