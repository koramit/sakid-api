<?php

namespace App\Http\Controllers;

use App\ServiceDomain;
use Illuminate\Http\Request;

class ServiceDomainController extends Controller
{
    /**
     *
     * Authenticated companion only
     *
    **/
    public function __construct()
    {
        $this->middleware('sakidGuard');
    }

    /**
     *
     * Store service domain
     * @param  Request $request
     * @return Array
     *
    **/
    public function store(Request $request)
    {
        $data = $request->all();
        $data['token'] = str_random(64);
        $domain = ServiceDomain::insert($data);

        return config('replycodes.ok') + ['token' => $data['token']];
    }

    public function update(Request $request)
    {
        $domain = ServiceDomain::find($request->service_domain_id);
        if ($domain->update($request->all())) {
            return config('replycodes.ok');
        }
        return config('replycodes.error');
    }
}
