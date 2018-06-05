<?php

namespace App\Contracts;

interface AutoId
{
    /**
     * Get auto Id type of the model.
     *
     * @return stirng
     */
    public static function getIdType();

    /**
     * Insert massive assignment to table.
     *
     * @param  array  $data
     * @return Illuminate\Database\Eloquent\Model
     */
    public static function insert(array $data);
}
