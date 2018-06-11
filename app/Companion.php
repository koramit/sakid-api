<?php

namespace App;

use GuzzleHttp\Client;

class Companion
{
    protected $client;
    protected $headers;

    /**
     *
     * Create http client
     *
     **/
    public function __construct()
    {
        $this->client = new Client([
                            'base_uri' => config('companion.url'),
                            'timeout'  => 8.0,
                        ]);

        $this->headers = [
            'Accept' => 'application/json',
            'token'  => config('companion.token'),
            'secret' => config('companion.secret')
        ];
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
            'headers' => $this->headers,
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

    /**
     *
     * Pass validated data to remote companion for
     * sending mail LINE verification to domain user
     * @param Array $data
     * @return Boolean
     *
     **/
    public function emailLINEQRCode($data)
    {
        $response = $this->client->post('/email-line-qrcode', [
            'headers' => $this->headers,
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
