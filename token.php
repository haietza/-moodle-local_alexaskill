<?php
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
        //CURLOPT_URL => 'http://0.0.0.0:8080/alexa/login/token.php/',
        CURLOPT_URL => 'https://alexa.haietza.com/login/token.php/',
        CURLOPT_POSTFIELDS => $values,
        CURLOPT_RETURNTRANSFER => 1
);
curl_setopt_array($ch, $options);
$data = curl_exec($ch);
if ($data === FALSE) {
    echo curl_error($ch);
}
curl_close($ch);

$obj = json_decode($data, true);
echo $obj['token'];
?>
