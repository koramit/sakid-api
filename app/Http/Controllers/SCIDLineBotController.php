<?php

namespace App\Http\Controllers;

use App\SCIDLineBot;
use App\ServiceDomain;
use App\LINEBotManager;
use Illuminate\Http\Request;

class SCIDLineBotController extends Controller
{
    public function __construct()
    {
        $this->middleware('scidGuard', ['except' => ['handleWebhook']]);
    }
    
    public function handleWebhook($botId, Request $request)
    {
        // $request = app('request');
        return (new LINEBotManager($request))->handleEvents(SCIDLineBot::find($botId));
    }

    public function store(Request $request)
    {
        // $data = app('request')->all();
        $data = $request->all();
        $data['service_domain_id'] = ServiceDomain::where('name', $data['service_domain_name'])->first()->id;
        $bot = SCIDLineBot::insert($data);

        // return response
        return $bot;
    }
}
