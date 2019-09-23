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

    protected $user;
    protected $domain;
    protected $request;

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

    /**
     *
     * Validating input for email user
     * @return
     *
     */
    protected function validInputsForEmail()
    {
        return (
            $this->request->has('username') ||
            filter_var($this->request->input('email'), FILTER_VALIDATE_EMAIL)
        );
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
     * Set $this->user by update or new
     *
     */
    protected function setUser()
    {
        if ( !$this->domain->willDuplicateUser($this->request->input('username')) ) {
            $user = $this->insertUser();
        } else {
            $user = User::where('service_domain_id', $this->domain->id)
                        ->where('name', $this->request->input('username'))
                        ->first();
            
            if ( $this->request->has('email') && $user->email !== $this->request->input('email') ) {
                $user->email = $this->request->input('email');
                $user->save();
            }
        }

        $this->user = $user;
    }

    /**
     *
     * Set data to fill up email template
     * @return array
     *
     */
    protected function getCommonEmailData()
    {
        // prepare data for email
        $data['email_sender'] = $this->domain->email_sender;
        $data['domain_name'] = $this->domain->name;
        $data['username'] = $this->user->name;
        $data['email_to'] = $this->user->email;
        if ( filter_var($this->domain->url, FILTER_VALIDATE_URL) ) {
            $data['url'] = $this->domain->url;
            $data['hostname'] = parse_url($this->domain->url)['host'];
        } else {
            $data['url'] = $this->domain->name;
            $data['hostname'] = $this->domain->name;
        }

        return $data;
    }

    /**
     *
     * Store domain user
     * @return Array
     *
    **/
    public function store()
    {
        if ( !$this->request->has('username') ) {
            return config('replycodes.bad');
        }

        if ( $this->domain->willDuplicateUser($this->request->input('username')) ) {
            return config('replycodes.duplicate_user');
        }

        $user = $this->insertUser();

        return config('replycodes.ok');
    }

    /**
     *
     * Delete domain user
     * @return Array
     *
    **/
    public function destroy()
    {
        if ( !$this->request->has('username') ) {
            return config('replycodes.bad');
        }

        $this->setUser();

        $this->user->delete();

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
        if ( !$this->validInputsForEmail() ) {
            return config('replycodes.bad');
        }

        $this->setUser();

        $data = $this->getCommonEmailData();

        $data['code'] = $this->genVerifyCode();

        $companion = new Companion;
        if ( $companion->emailVerifyCode($data) ) {
            return config('replycodes.ok') + ['verify_code' => $data['code']];
        } else {
            return config('replycodes.error');
        }
    }

    /**
     *
     * Email LINE QRCode to domain user for verification
     * @return Array
     *
    **/
    public function emailLINEQRCode()
    {
        if ( !$this->validInputsForEmail() ) {
            return config('replycodes.bad');
        }

        $this->setUser();

        if ( $this->user->line_bot_id === null ) {
            $this->user->line_bot_id = $this->domain->assignLineBot();
        }

        $this->user->line_verify_code = $this->genVerifyCode();
        $this->user->save();

        $data = $this->getCommonEmailData();

        $data['line_qrcode_url'] = $this->user->lineBot->qrcode_url;
        $data['line_verify_code'] = $this->user->line_verify_code;

        $companion = new Companion;
        if ( $companion->emailLINEQRCode($data) ) {
            return config('replycodes.ok');
        } else {
            return config('replycodes.error');
        }
    }

    /**
     *
     * Check if domain user verified by LINE
     * @return array
     *
     */
    public function checkLineVerified()
    {
        if ( !$this->request->has('username') ) {
            return config('replycodes.bad');
        }

        $user = User::where([
                        'service_domain_id' => $this->domain->id,
                        'name' => $this->request->username
                    ])
                    ->first();

        if ( $user == null ) {
            return config('replycodes.no_user');
        }

        if ( $user->line_user_id == null ) {
            return config('replycodes.unverified');
        }

        return config('replycodes.ok');
    }

    public function updateLineProfile($domainName, $username)
    {
        $domain = \App\ServiceDomain::whereName($domianName)->first();
        if (!$domian) return "";

        $user = User::whereServiceDomainId($domain->id)->whereName($username)->first();
        if (!$user) return "";

        return $user;
    }
}
