<?php

namespace App\Http\Controllers;

use Rap2hpoutre\LaravelLogViewer\LaravelLogViewer;
use Laravel\Lumen\Routing\Controller as BaseController;

class SecuredLogViewerController extends BaseController
{
    protected $request;

    public function __construct()
    {
        $this->request = app('request');
    }

    public function index()
    {
        $params = $this->request->all();

        if ( !array_key_exists('code', $params) || $params['code'] != env('LOG_VIEW_PASSCODE') ) {
            return "not allowed";
        }

        if ($this->request->input('l')) {
            LaravelLogViewer::setFile(base64_decode($this->request->input('l')));
        }

        if ($this->request->input('dl')) {
            return $this->download(LaravelLogViewer::pathToLogFile(base64_decode($this->request->input('dl'))));
        } elseif ($this->request->has('del')) {
            app('files')->delete(LaravelLogViewer::pathToLogFile(base64_decode($this->request->input('del'))));
            return $this->redirect($this->request->url());
        } elseif ($this->request->has('delall')) {
            foreach (LaravelLogViewer::getFiles(true) as $file) {
                app('files')->delete(LaravelLogViewer::pathToLogFile($file));
            }
            return $this->redirect($this->request->url());
        }

        return app('view')->make('laravel-log-viewer::log', [
            'logs' => LaravelLogViewer::all(),
            'files' => LaravelLogViewer::getFiles(true),
            'current_file' => LaravelLogViewer::getFileName()
        ]);
    }

    private function redirect($to)
    {
        if (function_exists('redirect')) {
            return redirect($to);
        }

        return app('redirect')->to($to);
    }

    private function download($data)
    {
        if (function_exists('response')) {
            return response()->download($data);
        }

        // For laravel 4.2
        return app('\Illuminate\Support\Facades\Response')->download($data);
    }
}
