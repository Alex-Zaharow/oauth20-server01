<?php
// include our OAuth2 Server object
require_once __DIR__.'/server.php';

$request = OAuth2\Request::createFromGlobals();
$response = new OAuth2\Response();

// validate the authorize request
if (!$server->validateAuthorizeRequest($request, $response)) {
    $response->send();
    die;
}

if (empty($_POST)) {
  exit('
<form method="post">
  <label>Do You Authorize '.$_GET["client_id"].' with grant '.(isset($_GET["scope"])==true ? $_GET["scope"] : "<empty>").'?</label><br />
  user <input type="text"   name="user_id"   value="user01"/> I need your boots shoes and your motocircle!<br/>
  <input type="submit" name="authorized" value="yes"/>
  <input type="submit" name="authorized" value="no"/>
</form>');
}

// print the authorization code if the user has authorized your client
$is_authorized = ($_POST['authorized'] === 'yes');
$is_authorized=true;

if(isset($_GET['client_id'])==false)
{
    exit("Не задан client_id.");
}
$client_id = ($_GET['client_id']);

if(isset($_POST['user_id'])==false)
{
    exit("Не задан user_id.");
}
$user_id = preg_replace ('/[^a-zA-Z0-9._]/', '', $_POST['user_id']);

// Если пользователя нет, то он будет создан:
$storage->setUser($user_id, "ghjnjy", 'FirstName-'.$user_id, 'LastName-'.$user_id);

$server->handleAuthorizeRequest($request, $response, $is_authorized, isset($user_id)==true ? $user_id : null);
if ($is_authorized) {
  // this is only here so that you get to see your code in the cURL request. Otherwise, we'd redirect back to the client
  $code = substr($response->getHttpHeader('Location'), strpos($response->getHttpHeader('Location'), 'code=')+5, 40);
  
  // После этого момента можно писать своё API. Главное почаще обращаться к базе access_tokens, чтобы проверять, что твориться с token'ом.
  //print_r($_POST);
  if(isset($_GET["redirect_uri"])==true)
  {
    // Достать из client-а redirect_uri, по которому надо вернуться:
    //echo "\n<br/>".$client_id;
    $client_info = $storage->getClientDetails($client_id);
    echo ''.json_encode($client_info);
    //echo "<br/>".$client_info["redirect_uri"];
    // В этой строке может воникнуть проблема, если у меня зарегистрировано несколько redirect_uri.
    // Надо не просто добавить к строке возврата не зарегистрированный redirect_uri в клиенте, а тот
    // который получен в запросе:
    //header('Location: '.$client_info["redirect_uri"].'?code='.$code.'&redirect_uri='.$client_info["redirect_uri"]);
    header('Location: '.$_GET["redirect_uri"].'?code='.$code.'&redirect_uri='.$_GET["redirect_uri"]);

  }
  exit("SUCCESS! Authorization Code: $code, for $user_id ".(isset($user_id)==true ? $user_id : " no_user"));
}
$response->send();
?>
