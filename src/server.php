<?php
session_start();

// error reporting (this is a demo, after all!)
ini_set('display_errors',1);error_reporting(E_ALL);

// Autoloading (composer is preferred, but for this example let's just do this)
require_once('OAuth2/Autoloader.php');
OAuth2\Autoloader::register();

$redis = new Redis();
$redis->connect("127.0.0.1");
//$redis->auth('12345');  // Если доступ к redis запаролен, то использовать эту команду, чтобы вайти в него.
$storage = new OAuth2\Storage\Redis($redis, array());  // Тут генерируется подключение к Redis, которое уже позволит манипулировать данными.
// Добавиь параметры клиента. В общем-то это надо делать только один раз, а так при использовании здесь этот параметр просто перезаписывается.
$storage->registerClient("client01",
                         "clientPassword",
                         "https://ds-sso.esphere.ru:8443/oauth2callback.sso-dev.php https://developers.google.com/oauthplayground https://ds-sso.esphere.ru:10443/openam/oauth2c/OAuthProxy.jsp",
                         "userinfo scope1"
                        );
//$storage->registerClient("client01", "clientPassword", "https://ds-sso.esphere.ru:10443/openam/oauth2c/OAuthProxy.jsp");  // Добавиь параметры клиента. В общем-то это надо делать только один раз, а так этот параметр просто перезаписывается
//$storage->setUser("user01", "ghjnjy", "user01FirstName", "user01LastName");  // тоже самое с пользователями. Они просто каждый раз пересоздаются или перезаписываются. Вдруг я базу переставлю а заново вводить их не хочется.
//$storage->setUser("user02", "ghjnjy", "user02FirstName", "user02LastName");
//$storage->setUser("user03", "ghjnjy", "user03FirstName", "user03LastName");
//$storage->setUser("user04", "ghjnjy", "user04FirstName", "user04LastName");

// Эта строка указана в документации к классу Redis, но она на самом деле просто показывает, как программно добавить
// нового клиента и в этом файле она совсем не нужна. Вообще это надо сделать отдельно.
//$storage->registerClient($client_id, $client_secret, $redirect_uri);

    // Pass a storage object or array of storage objects to the OAuth2 server class
    // В качестве параметра конфигурации передать  'require_exact_redirect_uri'=>false, чтобы 
    // было не строгое сравнение redirect_uri с redirect_uri, зарегистрированными в client:
    // enforce_state true/false - требовать или нет атрибута state в первоначальном запросе к серверу аутентификации (в форму логина, перед запросом авторизации)
    $server = new OAuth2\Server($storage, array('require_exact_redirect_uri'=>false, 'enforce_state'=>false) );
    // Add the "Client Credentials" grant type (it is the simplest of the grant types)
    $server->addGrantType(new OAuth2\GrantType\ClientCredentials($storage));
    // Add the "Authorization Code" grant type (this is where the oauth magic happens)
    $server->addGrantType(new OAuth2\GrantType\AuthorizationCode($storage));
    
//echo "<br/>scopeExists:".($storage->scopeExists('scope1', 'client02')==true ? "true" : "false");
?>
