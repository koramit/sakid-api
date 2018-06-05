<?php

namespace App;

use App\Contracts\AutoId;
use App\SCIDLineBot;
use App\Traits\AutoIdInsertable;
use App\Traits\DataCryptable;
use App\User;
use Illuminate\Database\Eloquent\Model;

class ServiceDomain extends Model implements AutoId
{
    use AutoIdInsertable, DataCryptable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'url',
        'name',
        'email',
        'token',
        'secret',
        'email_sender',
        'callback_url',
        'callback_token',
        'callback_secret',
        'line_follow_message',
        'line_greeting_message',
        'line_reply_unverified',
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'secret',
    ];

    /**
     * Set field 'secret'.
     *
     * @param string $value
     */
    public function setSecretAttribute($value)
    {
        $this->attributes['secret'] = $this->bcrypt($value);
    }

    public function users()
    {
        return $this->hasMany('App\User');
    }

    public function findUser($username)
    {
        return User::where('service_domain_id', $this->id)
                   ->where('name', $username)
                   ->first();
    }

    public function willDuplicateUser($username)
    {
        return User::where('service_domain_id', $this->id)->where('name', $username)->first() != null;
    }

    public function assignLineBot()
    {
        $bot = SCIDLineBot::where('service_domain_id', $this->id)
                       ->orderBy('qrcode_sent_count')
                       ->first();
                       
        $bot->countSent();

        return $bot->id;
    }
    
    public function sendCallback($username, $text)
    {
        if ( $this->callback_url != null ) {
            $url = parse_url($this->callback_url);
            $client = new \GuzzleHttp\Client([
                            'base_uri' => $url['scheme'] . '://' . $url['host'],
                            'timeout'  => 2.0,
                        ]);

            $response = $client->post($url['path'], [
                'headers' => [
                    'token'  => $this->callback_token,
                    'secret' => $this->callback_secret
                ],
                'form_params' => [
                    'platform' => 'line',
                    'username' => $username,
                    'text' => $text
                ]
            ]);

            if ( $response->getStatusCode() == 200 ) {
                return ['text' => 'OK'];
            }

            return ['text' => 'request failed'];
        }
    }
}
