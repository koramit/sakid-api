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

$router->get('/mongfat', function () use ($router) {
    return App\LINEEvent::with('lineBot')->orderBy('id', 'desc')->get();
});
$router->get('/mongdel', function () use ($router) {
    App\User::truncate();
    App\LINEWebhook::truncate();
    return 'done';
});

// Verify domain user by LINE
$router->post('/line-verify', 'UserController@lineVerify');
// $router->post('/line-verify', [
//         'middleware' => 'auth',
//         'uses' => 'UserController@lineVerify'
// ]);

// Check if domain user is verified by LINE
$router->post('/check-line-verify', 'UserController@checkLineVerify');
// $router->post('/check-line-verify', [
//         'middleware' => 'auth',
//         'uses' => 'UserController@checkLineVerify'
// ]);

// Domains push LINE message to they users
$router->post('/message/{platform}/push/{pushType}', 'MessagingController@pushMessage');
// $router->post('/message/{platform}/push/{pushType}', [
//         'middleware' => 'auth',
//         'uses' => 'MessagingController@pushMessage'
// ]);

$router->get('headers', function () {
    return app('request')->header();
});

$router->get('logs', 'SecuredLogViewerController@index');

// new callback
// $router->post('/{platform}/{botId}', 'BotEventsController@handle');
