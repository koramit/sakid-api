<?php

namespace App;

use App\Contracts\AutoId;
use App\Traits\DataCryptable;
use App\Traits\AutoIdInsertable;
use Illuminate\Database\Eloquent\Model;

class SAKIDLineBot extends Model implements AutoId
{
    use AutoIdInsertable, DataCryptable;

    protected $table = 'line_bots';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'name',
        'channel_secret',
        'service_domain_id',
        'channel_access_token',
    ];

    /**
     * Set field 'channel_access_token'.
     *
     * @param string $value
     */
    public function setChannelAccessTokenAttribute($value)
    {
        $this->attributes['channel_access_token'] = $this->encryptField($value);
    }

    /**
     * Get field 'channel_access_token'.
     *
     * @return string
     */
    public function getChannelAccessTokenAttribute()
    {
        return $this->decryptField($this->attributes['channel_access_token']);
    }

    /**
     * Set field 'channel_secret'.
     *
     * @param string $value
     */
    public function setChannelSecretAttribute($value)
    {
        $this->attributes['channel_secret'] = $this->encryptField($value);
    }

    /**
     * Get field 'channel_secret'.
     *
     * @return string
     */
    public function getChannelSecretAttribute()
    {
        return $this->decryptField($this->attributes['channel_secret']);
    }

    public function domain()
    {
        return $this->belongsTo('App\ServiceDomain', 'service_domain_id');
    }

    public function countSent()
    {
        $this->qrcode_sent_count++;
        $this->save();
    }

    public function countFollower()
    {
        $this->followers_count++;
        $this->save();
    }

    public function discountFollower()
    {
        $this->followers_count--;
        $this->save();
    }

    public function getQRCodeUrl()
    {
        $path = '/line/' . $this->domain->name . '/' . str_replace(' ', '_', $this->name) . '.png';
        return strtolower(url($path));
    }
}
