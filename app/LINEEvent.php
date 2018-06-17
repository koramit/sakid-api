<?php

namespace App;

use App\Contracts\AutoId;
use App\Traits\AutoIdInsertable;
use Illuminate\Database\Eloquent\Model;

class LINEEvent extends Model implements AutoId
{
    use AutoIdInsertable;

    protected $table = 'line_events';

    protected $fillable = [
        'id',
        'payload',
        'line_bot_id',
    ];

    /**
     * Get its related App\SAKIDLineBot model.
     *
     * @return App\SAKIDLineBot
     */
    public function lineBot()
    {
        return $this->belongsTo('App\SAKIDLineBot');
    }
}
