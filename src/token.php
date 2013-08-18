<?php
// include our OAuth2 Server object
require_once __DIR__.'/server.php';

//echo 'headers '.json_encode( apache_request_headers() ).' ';
//http_response_code(400);
//exit();
// Handle a request for an OAuth2.0 Access Token and send the response to the client
$request = OAuth2\Request::createFromGlobals();
// У этого $request есть одна проблема. При приходе запроса от OpenAM он оформлен не по стандарту.
// 1. header Authorization: OAuth 7615756172357123 -> должно быть Authorization: Bearer
// 2. access_token дублируется в GET, что я вляется нарушением стандарта. 
// Поэтому нужно исправить $request, чтобы он соответствовал стандарту.
// Для этого надо проверить, что header Authorization и GET содержат одинаковые значение access_token и оставить в запросе только GET.


//$headerAuthorization = $request->headers['AUTHORIZATION'];
//$get_access_token = $request->query('access_token'); // access_token из get

// Если заголовок авторизации написан как Authorization: OAuth 187236781638123, то извлечь из него access_token и сравнить с access_token 
// из get:
/*
if(!is_null($headerAuthorization) && !is_null($access_token_from_get) && false)
{
    $headerAuthorization = explode(' ', $headerAuthorization);
    $header_access_token = 'not set';
    if($headerAuthorization[0]=='OAuth')
    {
        $header_access_token = $headerAuthorization[1];
    }
    // Вот тут и надо убрать лишний параметр. Путь это будет header, потому что он всё равно оформлен не по стандарту:
    if($header_access_token==$get_access_token)
    {
        //$request->headers['Authorization']='Bearer '.$header_access_token;
        //$request->initialize($request->query, $request->request, $request->attributes, $request->cookies, $request->files, $request->content, $request->headers);
    }
}
//print_r($request->headers);
*/
$server->handleTokenRequest($request)->send();
//echo "\n";
?>
