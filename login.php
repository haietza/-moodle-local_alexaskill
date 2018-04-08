<html>
<body>

<?php
  $state = $_GET['state'];
  $client_id = $_GET['client_id'];
  $response_type = $_GET['response_type'];
  $redirect_uri = $_GET['redirect_uri'];
?>

<form action="/alexa/local/alexaskill/token.php" method="post">
  Username: <input type="text" name="username"><br>
  Password: <input type="password" name="password"><br>
  <input type="hidden" name="service" value="alexa_skill_service">
  State: <input type="text" name="state" value='<?php echo $state; ?>'><br>
  Client id: <input type="text" name="client_id" value='<?php echo $client_id; ?>'><br>
  Response type: <input type="text" name="response_type" value='<?php echo $response_type; ?>'><br>
  Redirect URI: <input type="text" name="redirect_uri" value='<?php echo $redirect_uri; ?>'><br>
  <input type="submit" value="Submit">
</form>

</body>
</html>
