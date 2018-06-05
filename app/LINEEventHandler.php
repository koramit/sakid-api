<?php

namespace App;


use App\LineBot as Bot;
use App\SCIDLineBot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use LINE\LINEBot;
use LINE\LINEBot\Constant\HTTPHeader;
use LINE\LINEBot\Exception\InvalidEventRequestException;
use LINE\LINEBot\Exception\InvalidSignatureException;
use LINE\LINEBot\HTTPClient\CurlHTTPClient;

class LINEEventHandler
{
    protected $lineBot;
    protected $events;

    public function __construct(Request &$request, $botId)
    {
        $signature[] = $request->header(HTTPHeader::LINE_SIGNATURE);
        if ( empty($signature) ) {
            return response('Bad Request', 400);
        }

        $scidBot = SCIDLineBot::find($botId);
        $this->lineBot = new LINEBot(
                            new CurlHTTPClient($scidBot->channel_access_token),
                            ['channelSecret' => $scidBot->channel_secret]
                        );
        
        try {
            $this->events = $this->lineBot->parseEventRequest($request->getContent(), $signature[0]);
        } catch (InvalidSignatureException $e) {
            return response('Invalid signature', 400);
        } catch (InvalidEventRequestException $e) {
            return response('Invalid event request', 400);
        }

        $this->events = $request->input('events');
    }

    public function handleEvents()
    {
        foreach ( $this->events as $event ) {
            if ( $event['type'] == 'message' ) {
                if ( $event['message']['type'] == 'text' ) {
                    $this->lineBot->replyText($event['replyToken'], $event['message']['text']);
                }
            }
        }
    }
}
