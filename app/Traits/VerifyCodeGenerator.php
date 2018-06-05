<?php

namespace App\Traits;

trait VerifyCodeGenerator
{
    public function genVerifyCode()
    {
        return str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
    }
}
