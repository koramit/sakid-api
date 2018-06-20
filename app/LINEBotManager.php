<?php

namespace App;

use Exception;
use LINE\LINEBot;
use App\LINEEvent;
use App\SAKIDLineBot;
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
     * @param array $events
     * @param integer $botId
     *
    */
    public function __construct($events, $botId)
    {
        $this->events = $events;
        $this->bot = SAKIDLineBot::find($botId);
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

            // if not unfollow event then initiate LINE bot client
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

    /**
     *
     * Handle follow event. This trigger by scan qrcode or unblocked
     * @param array $event
     * @return boolean
     *
     */
    protected function handleFollow($event)
    {
        // check if event trigger by user unblocked bot
        if ( $this->user != null ) {
            $this->user->line_unfollowed = false;
            $this->user->save();
            // ** IMPLEMENT ** //
            // some sticker here
            return $this->pushMessage('กลับมาทำไม ฉันลืมเธอไปหมดแล้ว', $event['source']['userId']);
        } else {
            $this->bot->countFollower();
        }

        // push LINE followed message
        return $this->pushMessage($this->bot->domain->line_follow_message, $event['source']['userId']);
    }

    /**
     *
     * Handle unfollow event
     * @param array $event
     * @return boolean
     *
     */
    protected function handleUnfollow($event)
    {
        // check if this user is domain registred user
        if ( $this->user != null ) {
            $this->user->line_unfollowed = true;
            $this->user->save();
        }

        $this->bot->discountFollower();
        return true;
    }

    /**
     *
     * Handle message event
     * @param array event
     * @return boolean
     *
     */
    protected function handleMessage($event)
    {
        // check if this event produce by domain registered user then service
        if ( $this->user !== null ) {
            if ( $event['message']['type'] == 'text' ) { // NOW support ONLY text message
                $response = $this
                            ->bot
                            ->domain
                            ->sendCallback($this->user->name, $event['message']['text']);

                $this->event->action_code = 1; // call back
                $this->event->response_code = $response['code'];
                return ($this->event->response_code > 1);
            }
        }

        if (    // check if non-registed user send text that may be a verification code
                $event['message']['type'] == 'text' &&   // message-type = text
                is_numeric($event['message']['text']) && // text = numeric
                (strlen($event['message']['text']) == 6) // text-length = 6
           ) {
            $this->event->action_code = 4; // verification
            if ( $this->verified($event['source']['userId'], $event['message']['text']) ) {
                return $this->replyMessage(
                    $this->bot->domain->line_greeting_message,
                    $event['replyToken']
                );
            }
        }

        // in case of non-registered user not send verify code or verify failed
        return $this->replyMessage(
            $this->bot->domain->line_reply_unverified,
            $event['replyToken']
        );
    }

    /**
     *
     * Handle LINE bot client response
     * @param object $response
     * @return boolean
     *
     */
    protected function handleResponse(&$response)
    {
        $this->event->response_code = $response->getHTTPStatus();

        return ( $this->event->response_code == 200 );
    }


    /**
     *
     * Push text message to user
     * @param string $text
     * @param string $userId
     * @return boolean
     *
     */
    protected function pushMessage($sms, $userId)
    {
        $textMessageBuilder = new TextMessageBuilder($sms);
        $response = $this->botClient->pushMessage($userId, $textMessageBuilder);

        $this->event->action_code = 2; // push sms
        return $this->handleResponse($response);
    }

    /**
     *
     * Reply text message to user
     * @param string $sms
     * @param string $replyToken
     * @return boolean
     *
     */
    protected function replyMessage($sms, $replyToken)
    {
        $textMessageBuilder = new TextMessageBuilder($sms);
        $response = $this->botClient->replyMessage($replyToken, $textMessageBuilder);

        $this->event->action_code = 3; // reply sms
        return $this->handleResponse($response);
    }

    /**
     *
     * Retrive user profile
     * @param string $userId
     * @return mixed
     *
     */
    protected function getUserProfile($userId)
    {
        $response = $this->botClient->getProfile($userId);
        if ($response->isSucceeded()) {
            $profile = $response->getJSONDecodedBody();
            return $profile;
        }

        return false;
    }

    /**
     *
     * Verifying code
     * @param  string $userId
     * @param  integer $verifyCode
     * @return boolean
     */
    protected function verified($userId, $verifyCode)
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

    /**
     *
     * Update user profile
     * @param  App\User $user
     * @return boolean
     *
     */
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
