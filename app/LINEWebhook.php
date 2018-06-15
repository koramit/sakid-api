<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $table = 'line_webhooks';

    protected $fillable = ['body'];
}