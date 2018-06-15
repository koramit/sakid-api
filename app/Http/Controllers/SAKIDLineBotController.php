<?php

namespace App\Http\Controllers;

use Exception;
use App\LINEWebhook;
use App\SAKIDLineBot;
// use App\ServiceDomain;
use App\LINEBotManager;
use Illuminate\Http\Request;

class SAKIDLineBotController extends Controller
{
    /**
     *
     * Authenticated companion only
     * except LINE webhook
     *
     **/
    public function __construct()
    {
        $this->middleware('sakidGuard', ['except' => ['handleWebhook']]);
    }

    public function store(Request $request)
    {
        try {
            $bot = SAKIDLineBot::insert($request->all());
            return config('replycodes.ok');
        } catch (Exception $e) {
            return config('replycodes.error');
        }
    }

    public function handleWebhook($botId, Request $request)
    {
        if ($request->has('events')) {
            // return 'has events';
            LINEWebhook::create(['body' => json_encode($request->input('events'))]);
            // $botManager = new LINEBotManager($request->input('events'));
            // return $botManager->handleEvents(SAKIDLineBot::find($botId));
        }

        return 'no events';
        // return (new LINEBotManager($request))->handleEvents(SAKIDLineBot::find($botId));
    }
}
