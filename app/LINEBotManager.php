<?php

namespace App;

use Exception;
use LINE\LINEBot;
use App\LINEWebhook;
use Illuminate\Support\Facades\Log;
use LINE\LINEBot\HTTPClient\CurlHTTPClient;
use LINE\LINEBot\MessageBuilder\TextMessageBuilder;

class LINEBotManager
{
    protected $webhook;
    protected $events;
    protected $user;
    protected $bot;

    public function __construct($events, $botId)
    {
        $this->events = $events;
        $this->bot = \App\SAKIDLineBot::find($botId);
        $this->user = $this->getUser($events[0]['source']['userId']);
    }

    public function handleEvents()
    {
        foreach ( $this->events as $event ) {
            $this->webhook = LINEWebhook::insert(['payload' => json_encode($event)]);
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
                    $result = false;
                    break;
            }
        }
    }

    protected function getUser($userId)
    {
        return User::where('service_domain_id', $this->bot->service_domain_id)
                    ->where('line_user_id', $userId)
                    ->first();
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
        return ['handle unfollow'];
    }

    protected function handleMessage($event)
    {
        if ( $this->user !== null ) { // service user
            if ( $event['message']['type'] == 'text' ) {
                $response = $this->bot->domain->sendCallback($user->name, $event['message']['text']);
            }
            return ['handle message'];
        }

        if (
                $event['message']['type'] == 'text' &&   // message-type = text
                is_numeric($event['message']['text']) && // text = numeric
                (strlen($event['message']['text']) == 6) // text-length = 6
           ) {
            if ( $this->isVerifyCodeMessage($event['source']['userId'], $event['message']['text']) ) {
                $this->replyMessage($this->bot->domain->line_greeting_message, $event['replyToken']);
                return ['handle message'];
            }
        }

        $this->replyMessage($this->bot->domain->line_reply_unverified, $event['replyToken']);
        return ['handle message'];
    }

    protected function makeLINEBot()
    {
        $httpClient = new CurlHTTPClient($this->bot->channel_access_token);
        return new LINEBot($httpClient, ['channelSecret' => $this->bot->channel_secret]);
    }

    protected function pushMessage($sms, $userId)
    {
        $bot = $this->makeLINEBot();

        $textMessageBuilder = new TextMessageBuilder($sms);
        $response = $bot->pushMessage($userId, $textMessageBuilder);

        return $response;
    }

    protected function replyMessage($sms, $replyToken)
    {
        $bot = $this->makeLINEBot();

        $textMessageBuilder = new TextMessageBuilder($sms);
        $response = $bot->replyMessage($replyToken, $textMessageBuilder);

        return $response;
    }

    protected function getUserProfile($userId)
    {
        $bot = $this->makeLINEBot();

        $response = $bot->getProfile($userId);
        if ($response->isSucceeded()) {
            $profile = $response->getJSONDecodedBody();
            return $profile;
        }

        return $this->bot;
    }

    protected function isVerifyCodeMessage($userId, $verifyCode)
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

        return true;
    }
}
