<?php

namespace App;

use App\Contracts\AutoId;
use App\Traits\AutoIdInsertable;
use Illuminate\Database\Eloquent\Model;

class LINEWebhook extends Model implements AutoId
{
    use AutoIdInsertable;

    protected $table = 'line_webhooks';

    protected $fillable = ['payload'];
}
