<?php

namespace App\Http\Controllers;

class MessagingController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
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
