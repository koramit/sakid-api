<?php

namespace App\Traits;

use App\ServiceDomain;

trait DomainAuthenticable
{
    public function authDomain(Array $header)
    {
        $domain = ServiceDomain::where('token', $header['token'][0])->first();

        if ( $domain === null ) {
            return false;
        }

        $hashPassword = app('db')->table('service_domains')
                                 ->select('secret')
                                 ->where('id', $domain->id)
                                 ->first()
                                 ->secret;

        if ( !password_verify($header['secret'][0], $hashPassword) ) {            
            return false;
        }

        return true;
    }

    public function getDomain(Array $header)
    {
        return ServiceDomain::where('token', $header['token'][0])->first();
    }
}
