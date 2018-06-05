<?php

namespace App;

use App\Contracts\AutoId;
use App\Traits\AutoIdInsertable;
use Illuminate\Database\Eloquent\Model;

class User extends Model implements AutoId
{
    use AutoIdInsertable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'name',
        'email',
        'line_bot_id',
        'line_verify_code',
        'service_domain_id',
    ];


    /**
     * Get its related App\SCIDLineBot model.
     *
     * @return App\SCIDLineBot
     */
    public function lineBot()
    {
        return $this->belongsTo('App\SCIDLineBot');
    }

    /**
     * Get userId by platform.
     *
     * @return String
     */
    public function getIdByPlatform($platform)
    {
        if ( $platform == 'line' ) {
            return $this->line_user_id;
        }
        return null;
    }
}
