<?php

namespace App\Traits;

use Illuminate\Support\Facades\Crypt;

trait DataCryptable
{
    /**
     * Use Illuminate\Contracts\Encryption\Encrypter to encrypt data.
     *
     * @param  string  $value
     * @return string|null
     */
    public function encryptField($value)
    {
        return ($value == '') ? null : Crypt::encrypt($value);
    }

    /**
     * Use Illuminate\Contracts\Encryption\Encrypter to decrypt data.
     *
     * @param  string  $value
     * @return string|null
     */
    public function decryptField($value)
    {
        return is_null($value) ? null : Crypt::decrypt($value);
    }

    /**
     * Use hmac with sha256 algorithm to hash data then get small portion by substr.
     *
     * @param  string  $value
     * @return string|null
     */
    public function miniHash($value)
    {
        return substr(hash_hmac("sha256", $value, config('app.key')), 13, 7);
    }

    /**
    * Hash the given value.
    *
    * @param  string  $value
    * @param  array   $options
    * @return string
    */
    public function bcrypt($value, $options = [])
    {
        return app('hash')->make($value, $options);
    }
}
