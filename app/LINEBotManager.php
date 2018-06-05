<?php

namespace App;

use App\LineBot as Bot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use LINE\LINEBot\MessageBuilder\TextMessageBuilder;
use \LINE\LINEBot;
use \LINE\LINEBot\HTTPClient\CurlHTTPClient;


class LINEBotManager
{
    protected $events;
    protected $bot;

    public function __construct(Request &$request)
    {
        if ( $request->has('events') ) {
            $this->events = $request->input('events');
            Log::info(json_encode($request->input('events')));
        }
    }

    public function handleEvents($bot)
    {
        $this->bot = $bot;
        foreach ( $this->events as $event ) {
            switch ($event['type']) {
                case 'follow':
                    $result = $this->handleFollow($event);
                    break;
                
                case 'unfollow':
                    $result = $this->handleUnfollow($event);
                    break;

                case 'message':
                    $result = $this->handleMessage($event);
                    break;

                default:
                    $result = 'not handle';
                    break;
            }
        }

        Log::info(json_encode($result));
    }

    protected function handleFollow($event)
    {
        $this->bot->countFollower();

        $user = User::where('service_domain_id', $this->bot->service_domain_id)
                    ->where('line_user_id', $event['source']['userId'])
                    ->first();

        if ( $user != null ) { // this line_user_id already verified with this domain
            $user->line_unfollowed = false;
            $user->save();
            return $this->pushMessage('กลัับมาทำไม ฉันลืมเธอไปหมดแล้ว', $event['source']['userId']);
        }
        
        // show LINE first follow message ** TESTED **
        return $this->pushMessage($this->bot->domain->line_follow_message, $event['source']['userId']);
    }

    protected function handleUnfollow($event)
    {
        $user = User::where('service_domain_id', $this->bot->service_domain_id)
                    ->where('line_user_id', $event['source']['userId'])
                    ->first();
        
        if ( $user != null ) {            
            $user->line_unfollowed = true;
            $user->save();
        }

        $this->bot->discountFollower();
        return ['handle unfollow'];
    }

    protected function handleMessage($event)
    {
        switch ($event['message']['type']) {
            case 'text':
                if ( $this->isVerifyCodeMessage($event['source']['userId'], $event['message']['text']) ) {
                    $this->replyMessage($this->bot->domain->line_greeting_message, $event['replyToken']);

                    break; // no action for now
                }

                // in case wrong verify code 
                if ( is_numeric($event['message']['text']) && (strlen($event['message']['text']) == 6) ) {
                    $this->replyMessage($this->bot->domain->line_reply_unverified, $event['replyToken']);
                    break;
                }

                // check if client provide callback function
                $user = User::where('service_domain_id', $this->bot->service_domain_id)
                                    ->where('line_user_id', $event['source']['userId'])
                                    ->first();
                if ( $user != null ) {
                    $response = $this->bot->domain->sendCallback($user->name, $event['message']['text']);
                    Log::info('Callback => ' . json_encode($response));
                }
                break;
            
            default:
                break;
        }
        return ['handle message'];
    }

    protected function getUserProfile($userId)
    {
        $httpClient = new CurlHTTPClient($this->bot->channel_access_token);
        $bot = new LINEBot($httpClient, ['channelSecret' => $this->bot->channel_secret]);
        $response = $bot->getProfile($userId);
        Log::info('$response->isSucceeded() => ' . $response->isSucceeded());
        if ($response->isSucceeded()) {
            // Log::info($response->getJSONDecodedBody());
            $profile = $response->getJSONDecodedBody();
            Log::info(json_encode($profile));
            return $profile;
        }

        return $this->bot;
    }

    protected function pushMessage($sms, $userId)
    {
        $httpClient = new CurlHTTPClient($this->bot->channel_access_token);
        $bot = new LINEBot($httpClient, ['channelSecret' => $this->bot->channel_secret]);

        $textMessageBuilder = new TextMessageBuilder($sms);
        $response = $bot->pushMessage($userId, $textMessageBuilder);

        return $response;
    }

    protected function replyMessage($sms, $replyToken)
    {
        $httpClient = new CurlHTTPClient($this->bot->channel_access_token);
        $bot = new \LINE\LINEBot($httpClient, ['channelSecret' => $this->bot->channel_secret]);

        $textMessageBuilder = new TextMessageBuilder($sms);
        $response = $bot->replyMessage($replyToken, $textMessageBuilder);

        return $response;
    }

    protected function isVerifyCodeMessage($userId, $verifyCode)
    {
        if ( !is_numeric($verifyCode) || (strlen($verifyCode) != 6) ) {
            return false;
        }

        $user = User::where('service_domain_id', $this->bot->service_domain_id)
                    ->where('line_bot_id', $this->bot->id)
                    ->whereNull('line_user_id')
                    ->where('line_verify_code', $verifyCode)
                    ->first();

        if ( $user == null ) {
            return false;
        }

        Log::info('Verify code message');

        $user->line_user_id = $userId;

        $profile = $this->getUserProfile($user->line_user_id);
        if (array_key_exists('displayName', $profile)) {
            $user->line_display_name = $profile['displayName'];
        }
        if (array_key_exists('pictureUrl', $profile)) {
            $user->line_picture_url = $profile['pictureUrl'];
        }
        if (array_key_exists('statusMessage', $profile)) {
            $user->line_status_message = $profile['statusMessage'];
        }
        $user->save();

        Log::info('User after line revified => ' . json_encode($user));

        return true;
    }
}
