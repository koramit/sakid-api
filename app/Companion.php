<?php

namespace App;

use GuzzleHttp\Client;

class Companion
{
    protected $client;

    /**
     *
     * Create http client
     *
     **/
    public function __construct()
    {
        $this->client = new Client([
                            'base_uri' => env('COMPANION_URL'),
                            'timeout'  => 8.0,
                        ]);
    }

    /**
     *
     * Pass validated data to remote companion for
     * sending mail verify code to domain user
     * @param Array $data
     * @return Boolean
     *
     **/
    public function emailVerifyCode($data)
    {
        $response = $this->client->post('/email-verify-code', [
            'headers' => [
                'Accept' => 'application/json',
                'token'  => env('COMPANION_TOKEN'),
                'secret' => env('COMPANION_SECRET')
            ],
            'form_params' => $data
        ]);

        if ( $response->getStatusCode() == 200 ) {
            $data = json_decode($response->getBody(), true);
            if ( $data['reply_code'] === 0 ) {
                return true;
            }
        }

        return false;
    }
}
