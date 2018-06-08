<?php

use Illuminate\Http\Request;

$router->get('/', function () use ($router) {
    return 'sakid-bot V 0.0.1 powered by lullabears.co';
});

// Create service domian
$router->post('/service-domain', 'ServiceDomainController@store');

// Create domain user
$router->post('/register-user', 'UserController@store');

// Send verify email
$router->post('/email-verify-code', 'UserController@emailVerifyCode');

// Create LINE bot
$router->post('/line-bot', 'SCIDLineBotController@store');
// $router->post('/line-bot', [
//         'middleware' => 'scidGuard',
//         'uses' => 'SCIDLineBotController@store'
// ]);

// LINE webhook
$router->post('/line-bot/{botId}', 'SCIDLineBotController@handleWebhook');

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