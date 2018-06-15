<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class LINEWebhook extends Model
{
    protected $table = 'line_webhooks';

    protected $fillable = ['body'];
}