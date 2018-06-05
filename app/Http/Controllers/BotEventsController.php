<?php

namespace App\Http\Controllers;

use App\SCIDLineBot;
use App\ServiceDomain;

class BotEventsController extends Controller
{
    protected $handler;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $request = app('request');

        $parts = explode('/', $request->url());

        if ( $parts[3] == 'line-bot' ) {
            $this->handler = new \App\LINEEventHandler($request, $parts[4]);
        } else {
            $this->handler = null;
        }

    }

    public function handle()
    {
        if ( $this->handler != null ) {
            $this->handler->handleEvents();
        }
    }
}
