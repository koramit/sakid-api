<?php

namespace App\Http\Controllers;

use App\User;
use App\Companion;
use Illuminate\Http\Request;
use App\Traits\DomainAuthenticable;
use App\Traits\VerifyCodeGenerator;

class UserController extends Controller
{
    use DomainAuthenticable, VerifyCodeGenerator;

    protected $domain;
    protected $request;

    /**
     *
     * Authenticated domain only
     *
    **/
    public function __construct(Request $request)
    {
        $this->middleware('auth');

        $this->request = $request;
        $this->domain = $this->getDomain($this->request->header());
    }

    /**
     *
     * Insert domain user
     * @return App\User
     *
    **/
    protected function insertUser()
    {
        $data = $this->request->all();
        $data['name']              = $this->request->input('username');
        $data['service_domain_id'] = $this->domain->id;
        return User::insert($data);
    }

    /**
     *
     * Store domain user
     * @param  Request $request
     * @return Array
     *
    **/
    public function store()
    {
        if ( $this->domain->willDuplicateUser($this->request->input('username')) ) {
            return config('replycodes.duplicate_user');
        }

        $user = $this->insertUser();

        return config('replycodes.ok');
    }

    /**
     *
     * Email verify code to domain user
     * @return Array
     *
    **/
    public function emailVerifyCode()
    {
        // validate inputs [username, email]
        if ( !$this->request->has('username') || !filter_var($this->request->input('email'), FILTER_VALIDATE_EMAIL)) {
            return config('replycodes.bad');
        }

        // if user not exists then create
        if ( !$this->domain->willDuplicateUser($this->request->input('username')) ) {
            $user = $this->insertUser();
        } else {
            $user = User::where('service_domain_id', $this->domain->id)
                        ->where('name', $this->request->input('username'))
                        ->first();
            if ( $user->email !== $this->request->input('email') ) {
                $user->email = $this->request->input('email');
                $user->save();
            }
        }

        // send email
        $data['email_sender'] = $this->domain->email_sender;
        $data['domain_name'] = $this->domain->name;
        $data['username'] = $user->name;
        $data['email_to'] = $user->email;
        $data['code'] = $this->genVerifyCode();

        if ( filter_var($this->domain->url, FILTER_VALIDATE_URL) ) {
            $data['url'] = $this->domain->url;
            $data['hostname'] = parse_url($this->domain->url)['host'];
        } else {
            $data['url'] = $this->domain->name;
            $data['hostname'] = $this->domain->name;
        }

        $companion = new Companion;
        if ( $companion->emailVerifyCode($data) ) {
            return config('replycodes.ok') + ['verify_code' => $data['code']];
        } else {
            return config('replycodes.error');
        }
    }

    public function checkLineVerify()
    {
        if ( !app('request')->has('service_domain_name') || !app('request')->has('username') ) {
            return config('replycodes.bad');
        }

        // garantee exits from middleware
        $domain = \App\ServiceDomain::where('name', app('request')->input('service_domain_name'))->first();

        $user = \App\User::where('service_domain_id', $domain->id)
                    ->where('name', app('request')->input('username'))
                    ->first();

        if ( $user == null ) {
            return config('replycodes.no_user');
        }

        if ( $user->line_user_id == null ) {
            return config('replycodes.unverified');
        }

        return config('replycodes.ok');
    }

    public function lineVerify()
    {
        if ( !app('request')->has('service_domain_name') || !app('request')->has('username') ) {
            return config('replycodes.bad');
        }

        $domain = \App\ServiceDomain::where('name', app('request')->input('service_domain_name'))->first();

        if ( $domain->willDuplicateUser(app('request')->input('username')) ) {
            return config('replycodes.duplicate_user');
        }

        $data = app('request')->all();

        $data['name']              = app('request')->input('username');
        $data['line_bot_id']       = $domain->assignLineBot();
        $data['line_verify_code']  = $this->genVerifyCode();
        $data['service_domain_id'] = $domain->id;

        $user = \App\User::insert($data);

        return [
            'reply_code'       => 0,
            'line_qrcode_url'  => $user->lineBot->getQRCodeUrl(),
            'line_verify_code' => $data['line_verify_code'],
        ];
    }
}
