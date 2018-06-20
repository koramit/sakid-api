<?php

use Illuminate\Http\Request;
use Illuminate\Filesystem\Cache;

$router->get('/', function () use ($router) {
    return 'sakid-bot V 0.0.1 powered by lullabears.co';
});

// Create service domian
$router->post('/service-domain', 'ServiceDomainController@store');

// Create domain user
$router->post('/register-user', 'UserController@store');

// Email verify code
$router->post('/email-verify-code', 'UserController@emailVerifyCode');

// Create LINE bot
$router->post('/line-bot', 'SAKIDLineBotController@store');

// Email LINE QRCode
$router->post('/email-line-qrcode', 'UserController@emailLINEQRCode');

// LINE webhook
$router->post('/line-bot-webhook/{botId}', 'SAKIDLineBotController@handleWebhook');

// Check if domain user is verified by LINE
$router->post('/check-line-verified', 'UserController@checkLineVerified');

$router->get('/mongfat', function () use ($router) {
    return App\LINEEvent::orderBy('id', 'desc')->get();
});
$router->get('/show-users', function () use ($router) {
    return App\User::where('service_domain_id', 1)->get();
});
$router->get('/clear-user-by-id/{id}', function ($id) use ($router) {
    App\User::where('id', $id)->delete();
    return 'user id '  . $id .' deleted.';
});

// Domains push LINE message to they users
$router->post('/message/{platform}/push/{pushType}', 'MessagingController@pushMessage');
// $router->post('/message/{platform}/push/{pushType}', [
//         'middleware' => 'auth',
//         'uses' => 'MessagingController@pushMessage'
// ]);

$router->get('logs', 'SecuredLogViewerController@index');