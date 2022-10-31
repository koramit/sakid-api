<?php

namespace App\LINE;

class LINEPusher
{
    protected $bot;

    public function __construct(\LINE\LINEBot $bot)
    {
        $this->bot = $bot;
    }

    public function push($data, $userId, $pushType)
    {
        if ( $pushType == 'text' ) {
            // handle text
            if ( !array_key_exists('text', $data) ) {
                return config('replycodes.bad');
            }

            $response = $this->bot->pushMessage(
                                    $userId,
                                    new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($data['text'])
                                );
        } elseif ( $pushType == 'sticker' ) {
            // handle sticker
            if ( !array_key_exists('package_id', $data) || !array_key_exists('sticker_id', $data) ) {
                return config('replycodes.bad');
            }

            $response = $this->bot->pushMessage(
                                    $userId,
                                    new \LINE\LINEBot\MessageBuilder\StickerMessageBuilder(
                                                                    $data['package_id'],
                                                                    $data['sticker_id']
                                                                )
                                );
        } else {
            return config('replycodes.not_done');
        }

        if ( $response->getHTTPStatus() != 200 ) {
            $error = config('replycodes.error');
            $error['reply_text'] .= (' with status ' . $response->getHTTPStatus());
            return config('replycodes.error');
        }

        return config('replycodes.ok');
    }
}
