<?php

namespace App;

use Exception;
use LINE\LINEBot;
use App\LINEEvent;
use Illuminate\Support\Facades\Log;
use LINE\LINEBot\HTTPClient\CurlHTTPClient;
use LINE\LINEBot\MessageBuilder\TextMessageBuilder;

class LINEBotManager
{
    protected $bot;         // sakid LINE bot
    protected $user;        // sakid user
    protected $event;       // LINE event
    protected $events;      // LINE events
    protected $botClient;   // LINEBot client for LINE platform

    /**
     *
     * Initiate instance
     * @param array $events, integer $botId
     *
    */
    public function __construct($events, $botId)
    {
        $this->events = $events;
        $this->bot = \App\SAKIDLineBot::find($botId);
    }

    /**
     *
     * Handle LINE events from webhook
     * 
    */
    public function handleEvents()
    {
        foreach ( $this->events as $event ) {
            // check if this user are registered
            $this->user = User::where('service_domain_id', $this->bot->service_domain_id)
                              ->where('line_user_id', $event['source']['userId'])
                              ->first();

            // create LINE event record
            $this->event = LINEEvent::insert([
                'line_bot_id' => $this->bot->id,
                'payload' => json_encode($event),
                'userId'  => $event['source']['userId']
            ]);

            if ( $event['type'] != 'unfollow' ) {
                $httpClient = new CurlHTTPClient($this->bot->channel_access_token);
                $this->botClient = new LINEBot($httpClient, ['channelSecret' => $this->bot->channel_secret]);
            }
            
            // handle event by type
            switch ($event['type']) {
                case 'follow':
                    $handleable = $this->handleFollow($event);
                    break;
                case 'unfollow':
                    $handleable = $this->handleUnfollow($event);
                    break;
                case 'message':
                    $handleable = $this->handleMessage($event);
                    break;
                default:
                    $handleable = false;
                    break;
            }
            $this->event->handleable = $handleable;
            $this->event->save();
        }
    }

    protected function handleFollow($event)
    {
        // check if user already register with this bot
        if ( $this->user != null ) {
            $this->user->line_unfollowed = false;
            $this->user->save();
            return $this->pushMessage('กลับมาทำไม ฉันลืมเธอไปหมดแล้ว', $event['source']['userId']);
        } else {
            $this->bot->countFollower();
        }

        // push LINE followed message
        return $this->pushMessage($this->bot->domain->line_follow_message, $event['source']['userId']);
    }

    protected function handleUnfollow($event)
    {
        if ( $this->user != null ) {
            $this->user->line_unfollowed = true;
            $this->user->save();
        }

        $this->bot->discountFollower();
        return true;
    }

    protected function handleMessage($event)
    {
        if ( $this->user !== null ) { // service user
            if ( $event['message']['type'] == 'text' ) {
                $response = $this->bot->domain->sendCallback($user->name, $event['message']['text']);
                
                $this->event->action_code = 1; // call back
                $this->event->response_code = $response['code'];
                if ( $this->event->response_code > 1 ) {
                    return false;
                }
                return true;
            }
        }

        if (
                $event['message']['type'] == 'text' &&   // message-type = text
                is_numeric($event['message']['text']) && // text = numeric
                (strlen($event['message']['text']) == 6) // text-length = 6
           ) {
            $this->event->action_code = 4; // verification
            if ( $this->tryVerify($event['source']['userId'], $event['message']['text']) ) {
                return $this->replyMessage($this->bot->domain->line_greeting_message, $event['replyToken']);
            }
        }

        return $this->replyMessage($this->bot->domain->line_reply_unverified, $event['replyToken']);
    }

    protected function handleResponse(&$response)
    {
        $this->event->response_code = $response->getHTTPStatus();

        if ( $this->event->response_code == 200 ) {
            return true;
        }

        return false;
    }

    protected function pushMessage($sms, $userId)
    {
        $textMessageBuilder = new TextMessageBuilder($sms);
        $response = $this->botClient->pushMessage($userId, $textMessageBuilder);

        $this->event->action_code = 2; // push sms
        return $this->handleResponse($response);
    }

    protected function replyMessage($sms, $replyToken)
    {
        $textMessageBuilder = new TextMessageBuilder($sms);
        $response = $this->botClient->replyMessage($replyToken, $textMessageBuilder);

        $this->event->action_code = 3; // reply sms
        return $this->handleResponse($response);
    }

    protected function getUserProfile($userId)
    {
        $response = $this->botClient->getProfile($userId);
        if ($response->isSucceeded()) {
            $profile = $response->getJSONDecodedBody();
            return $profile;
        }

        return false;
    }

    protected function tryVerify($userId, $verifyCode)
    {
        $user = User::where([
                        'line_user_id' => null,
                        'service_domain_id' => $this->bot->service_domain_id,
                        'line_bot_id' => $this->bot->id,
                        'line_verify_code' => $verifyCode
                    ])
                    ->first();

        if ( $user == null ) {
            return false;
        }

        $user->line_user_id = $userId;
        $user->save();

        return $this->updateUserProfile($user);
    }

    public function updateUserProfile($user)
    {
        $profile = $this->getUserProfile($user->line_user_id);
        
        if ( $profile === false ) {
            return false;
        }

        if (array_key_exists('displayName', $profile)) {
            $user->line_display_name = $profile['displayName'];
        }
        if (array_key_exists('pictureUrl', $profile)) {
            $user->line_picture_url = $profile['pictureUrl'];
        }
        if (array_key_exists('statusMessage', $profile)) {
            $user->line_status_message = $profile['statusMessage'];
        }

        return $user->save();
    }
}
