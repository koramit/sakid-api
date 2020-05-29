<?php

namespace App\Http\Controllers;

use LINE\LINEBot;
use Illuminate\Http\Request;
use App\Traits\DomainAuthenticable;
use LINE\LINEBot\HTTPClient\CurlHTTPClient;
use LINE\LINEBot\MessageBuilder\TextMessageBuilder;
use LINE\LINEBot\MessageBuilder\ImageMessageBuilder;
use LINE\LINEBot\MessageBuilder\StickerMessageBuilder;
use LINE\LINEBot\MessageBuilder\LocationMessageBuilder;

class MessagingController extends Controller
{
    use DomainAuthenticable;

    protected $request;
    protected $domain;

    /**
     *
     * Authenticated domain only
     * @param Illuminate\Http\Request $request
     *
     **/
    public function __construct(Request $request)
    {
        $this->middleware('auth');

        $this->request = $request;
        $this->domain = $this->getDomain($this->request->header());
    }

    protected function lineMessageBuilder()
    {
        if ( !$this->request->has('type') ) {
            return null;
        }

        if ( $this->request->input('type') == 'text' ) {
            if ( !$this->request->has('text') ) {
                return null;
            }
            return new TextMessageBuilder($this->request->input('text'));

        } elseif ( $this->request->input('type') == 'sticker' ) {
            if ( !$this->request->has('package_id') || !$this->request->has('sticker_id') ) {
                return null;
            }
            return new StickerMessageBuilder(
                $this->request->input('package_id'),
                $this->request->input('sticker_id')
            );
        } elseif ($this->request->input('type') == 'image') {
            if ( !$this->request->has('original_url') || !$this->request->has('preview_url') ) {
                return null;
            }
            return new ImageMessageBuilder(
                $this->request->input('original_url'),
                $this->request->input('preview_url')
            );
        } elseif ($this->request->input('type') == 'location') {
            if ( 
                !$this->request->has('title') || 
                !$this->request->has('address') || 
                !$this->request->has('latitude') || 
                !$this->request->has('longitude') 
            ) {
                return null;
            }
            return new LocationMessageBuilder(
                $this->request->input('title'),
                $this->request->input('address'),
                $this->request->input('latitude'),
                $this->request->input('longitude') 
            );
        } else {
            return null;
        }
    }

    public function lineMessaging()
    {
        if (!$this->request->has('username')) {
            return config('replycodes.bad');
        }

        $user = $this->domain->findUser($this->request->input('username'));

        if (!$user) {
            return config('replycodes.bad');
        }

        $bot = new LINEBot(
                    new CurlHTTPClient($user->lineBot->channel_access_token),
                    ['channelSecret' => $user->lineBot->channel_secret]
               );

        $isReply = $this->request->has('reply_token');

        $message = $this->lineMessageBuilder();
        if ( $message === null ) {
            return config('replycodes.bad');
        }

        if ( $isReply ) {
            $response = $bot->replyMessage($this->request->input('reply_token'), $message);
        } else {
            $response = $bot->pushMessage($user->line_user_id, $message);
        }

        if ( $response->getHTTPStatus() == 200 ) {
            return config('replycodes.ok');
        }

        return config('replycodes.error') + ['response_status' => $response->getHTTPStatus()];

        return config('replycodes.error');
    }

    /**
     * Push message to the platform provider.
     *
     * @return Array
     */
    public function pushMessage()
    {
        $domain = \App\ServiceDomain::where('token', app('request')->header('token'))->first();
        $pushType = explode('/', app('request')->url())[6];
        $platform = explode('/', app('request')->url())[4];
        $user = app('request')->has('username') ?
                      $domain->findUser(app('request')->username) :
                      null;

        if ( ($user == null) ) {
            return config('replycodes.bad_or_no_user');
        }
        $userId = $user->getIdByPlatform($platform);

        if ( $userId == null ) {
            return config('replycodes.unfollowed');
        }

        if ( $platform == 'line' ) {
            $pusher = new \App\LINE\LINEPusher(
                                new \LINE\LINEBot(
                                    new \LINE\LINEBot\HTTPClient\CurlHTTPClient(
                                        $user->lineBot->channel_access_token
                                    ),
                                    [
                                        'channelSecret' => $user->lineBot->channel_secret
                                    ]
                                )
                            );
        } else { // not yet implement for another platform
            $pusher = null;
        }

        return $pusher->push(app('request')->all(), $userId, $pushType);
    }
}
