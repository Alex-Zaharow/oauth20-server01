<?php
$arrHeaders = apache_request_headers();
// include our OAuth2 Server object
require_once __DIR__.'/server.php';

$request = OAuth2\Request::createFromGlobals();

// По стандарту oAuth (RFC 6749) параметр access_token при запросах к серверу авторизации для получения информации и пользователе  делается посылкой
// заголовка Authorization: Bearer <access_token>
// Поэтому анализировать надо на параметры _GET, _POST, а headers. При этом надо помнить, что и инициация запроса параметров пользователя
// должна быть должна быть сформирована с добавление заголовка в запрос. (Чтобы попасть сюда).

$arrAuthorization = 'no authorization'; 
foreach ($arrHeaders as $key => $value)
{
  // HTTP_Request2 как-то странно формирует имя заголовка content-header (верблюдом), а google записывает его
  // маленькими буквами, для браузеров это может и не важно, но в этом случае оказалось, что важно, поэтому
  // вручную проверяем ключи заголовков: 
  if(strtolower($key)=='authorization')
  {
      $arrAuthorization=explode(' ', $value);
      switch($arrAuthorization[0])
      {
          case 'OAuth':
          case 'Bearer':
              break;
          default: 
              http_response_code('400');
              exit('here must be only Bearer authorization.');
      }
      break;
  }
}

// Сравнить access_token в заголовке и в GET
$headerAuthorization = $request->headers['AUTHORIZATION'];
$get_access_token = $request->query('access_token'); // access_token из get
//echo ' get_access_token:'.$get_access_token.', headerAuthorization:'.$headerAuthorization;
if(!is_null($headerAuthorization)) // && !is_null($get_access_token) ) 
{
    $headerAuthorization = explode(' ', $headerAuthorization);
    $header_access_token = 'not set';
    if($headerAuthorization[0]=='OAuth')
    {
        $header_access_token = $headerAuthorization[1];
    }
    $request->headers['AUTHORIZATION']='Bearer '.$header_access_token;
    unset($request->query['access_token']);
    //$request->initialize($request->query, $request->request, $request->attributes, $request->cookies, $request->files, $request->content, $request->headers);
}
/*
echo ' request->headers:';
print_r($request->headers);
echo ' request->query:';
echo json_encode($request->query, JSON_PRETTY_PRINT);
http_response_code(400);
exit;
//*/


$format = 'not set';
foreach ($arrHeaders as $key => $value)
{
  // HTTP_Request2 как-то странно формирует имя заголовка content-header (верблюдом), а google записывает его
  // маленькими буквами, для браузеров это может и не важно, но в этом случае оказалось, что важно, поэтому
  // вручную проверяем ключи заголовков: 
  if(strtolower($key)=='content-type')
  {
      $format=$value;
      break;
  }
}

// Handle a request for an OAuth2.0 Access Token and send the response to the client

if (!$server->verifyResourceRequest( $request )) {
    $server->getResponse()->send();
    echo "\n";
        die;
}

switch($format)
{
    case 'application/json':
    default:
        $access_token_info = $storage->getAccessToken( $arrAuthorization[1] );
        $user_id = $access_token_info["user_id"];
        $user_info = $storage->getUserDetails($user_id);
        echo json_encode($user_info, JSON_PRETTY_PRINT )."\n";
        break;
        //echo 'формат ['.$format.'] не поддерживается';  
        //break;
}
?>
