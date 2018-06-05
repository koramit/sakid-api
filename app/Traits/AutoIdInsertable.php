<?php

namespace App\Traits;

use Ramsey\Uuid\Uuid;

trait AutoIdInsertable
{
    /**
     * Generate auto Id by selected type then add it to $data before call Eloquent model create($data).
     *
     * @param  array  $data
     * @return Illuminate\Database\Eloquent\Model
     */
    public static function insert(array $data = [])
    {
        switch (static::getIdType()) {
            case 'id':
                $data['id'] = static::count() + 1;
                break;
            case 'time_based_uuid':
                $data['id'] = Uuid::uuid1()->toString();
                break;
            case 'random_uuid':
                $data['id'] = Uuid::uuid4()->toString();
                break;
        }

        static::create($data);
        return static::find($data['id']);
    }

    /**
     * This make default id type of model
     * is the convention increment
     *
     * @return stirng
     */
    public static function getIdType()
    {
        return 'id';
    }
}
