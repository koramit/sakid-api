<?php

namespace App;

use GuzzleHttp\Client;

class Companion
{
    protected $client;

    public function __construct()
    {
        $this->client = new Client([
                            'base_uri' => env('COMPANION_URL'),
                            'timeout'  => 8.0,
                        ]);
    }

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

        return $response->getStatusCode() == 200 ? true : false;
        if ( $response->getStatusCode() == 200 ) {
            
            // return json_decode($response->getBody(), true);
        }

        return config('replycodes.error');
    }
}
