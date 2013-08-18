<?php
require_once __DIR__.'/server.php';

// Эта страница является промежуточной в этом шлюзе. Попасть на эту страницу пользователь должен после авторизации на google.
// google возвращает параметры, авторизации. Эта страница должна получить данные пользователя и запомнить из в redis вместе
// с именем пользователя. После этого будет отправлен redirect с кодом аутентификации на шлюзе.
// Переход на эту страницу инициируется сервером авторизации, но пользователь указывает эту страницу в качестве страницы назначения после того
// как сервер авторизации получит подтверждение от пользователя на доступ к данным. При переходе на эту страницу указывается параметр code=....
// именно этот параметр надо обменять на access_token.
//

//*
//*/
//echo "".$_GET["code"];
//echo "<br/>".$_GET["code"];

require "HTTP/Request2.php";

// Так как пользователь авторизовал доступ к своим данным, то нужно получить получить параметры авторизации от сервера google:
$url = "https://accounts.google.com/o/oauth2/token";  // endpint для параметров авторизации
//$url = "https://ds-sso.esphere.ru:8443/post.php";
$r = new HTTP_Request2($url, HTTP_Request2::METHOD_POST);
$r->setConfig(
                array(
                        'ssl_verify_peer'       => false,
                        'ssl_verify_host'       => false,
//                      'ssl_cafile'            => '/opt/apache/httpd-2.4.4/conf/ssl/thawte_Primary_Root_CA.pem'
                        'ssl_cafile'            => '/opt/apache/httpd-2.4.4/conf/ssl/GeoTrust_Primary_CA.pem'
                        )
                );
$r->addPostParameter("code", $_GET["code"]);
$r->addPostParameter("client_id", "676717674921-qla7qds8mt48qa6kuhtn6mf15g2hvcp6.apps.googleusercontent.com");
$r->addPostParameter("client_secret", "X0sk6vqefoKrYouFG-1zZNF6");
$r->addPostParameter("redirect_uri", "https://sso-dev.esphere.ru:10443/oauth2-server-php/src/oauth2callback.google.php");
$r->addPostParameter("grant_type", "authorization_code");
//$r->addPostParameter("", "");
$strJSONToken="";
$arrToken = "";
try
{
    $response = $r->send();
    if( 200 == $response->getStatus() )
    {
        $strJSONToken = $response->getBody();
        $arrToken = json_decode($strJSONToken, true);  // { "access_token" : "ya29....", "token_type" : "Bearer", "expires_in" : 3546, "id_token" : "...." }
        setcookie("sso-dev_google_oauth20_access_token", $arrToken["access_token"], time()+$arrToken["expires_in"], "/", ".esphere.ru");  // установим куку для access_token
        //echo "<br/>".$strJSONToken;
    }
    else
    {
        echo "<br/><b>Server response status: ".$response->getStatus()." ". $response->getReasonPhrase() . ", ". $response->getBody() ."</b>";
        exit;
    }
}
catch(HTTP_Request2_Exception $e)
{
    echo 'Error: ' . $e->getMessage();
    exit;
}

//return 0;

// Имея access_token можно запросить данные о пользователе:
$url = "https://www.googleapis.com/oauth2/v1/userinfo?access_token=".$arrToken["access_token"];
$r1 = new HTTP_Request2($url, HTTP_Request2::METHOD_GET);
$r1->setConfig(
                array(
                        'ssl_verify_peer'       => false,
                        'ssl_verify_host'       => false,
//                      'ssl_cafile'            => '/opt/apache/httpd-2.4.4/conf/ssl/thawte_Primary_Root_CA.pem'
                        'ssl_cafile'            => '/opt/apache/httpd-2.4.4/conf/ssl/GeoTrust_Primary_CA.pem'
                        )
                );
$strJSON="";
$userOAuth20 = "";
try
{
    $response = $r1->send();
    if( 200 == $response->getStatus() )
    {
        $strJSON = $response->getBody();
        $userOAuth20 = json_decode($strJSON, true);  // {"id": "...", "email": "....", "verified_email": true, "name": "XXX", "given_name": "...", "family_name": "XXX", "link": "https://plus.google.com/....", "picture": "https://lh6.googleusercontent.com/...photo.jpg", "gender": "male", "birthday": "nnnnnnn", "locale": "ru"}
        $_SESSION["google_params"] = $strJSON;
        $_SESSION["access_token"]=$arrToken["access_token"];
        $_SESSION["id"]         =$userOAuth20["id"];
        $_SESSION["email"]      =$userOAuth20["email"];
        $_SESSION["name"]       =$userOAuth20["name"];
        $_SESSION["link"]       =$userOAuth20["link"];
        $_SESSION["picture"]    =$userOAuth20["picture"];
        $storage->setUser($userOAuth20["id"], "password", $arrToken, "");
        // "https://sso-dev.esphere.ru:10443/oauth2-server-php/src/authorize.php?response_type=code&client_id=client01&state=xyz&user_id=".$userOAuth20["id"]."&redirect_uri=https://ds-sso.esphere.ru:8443/oauth2callback.sso-dev.php
        //header( 'Location: /index.php', true, 301 ); // Если не возникло исключения, то access_token получен и можно перенаправить на исходную страницу.
        echo "\n<br/>".$strJSON;
        header( 'Location: https://sso-dev.esphere.ru:10443/oauth2-server-php/src/authorize.php?response_type=code&client_id=client01&state=xyz&user_id='.$userOAuth20["id"].'&redirect_uri=https://ds-sso.esphere.ru:8443/oauth2callback.sso-dev.php', true, 301 ); // Если не возникло исключения, то access_token получен и можно перенаправить на исходную страницу.
    }
    else
    {
        echo "<br/><b>userinfo: Server response status: ".$response->getStatus()." ". $response->getReasonPhrase() . ", ". $response->getBody() ."</b>";
        exit;
    }
}
catch(HTTP_Request2_Exception $e)
{
    echo 'Error: ' . $e->getMessage();
    exit;
}
?>