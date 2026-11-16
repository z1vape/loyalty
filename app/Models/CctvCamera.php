<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CctvCamera extends Model
{
    protected $fillable = [
        'store_id',
        'name',
        'location_tag',
        'stream_type',
        'stream_url',
        'ip_address',
        'port',
        'channel',
        'username',
        'password',
        'is_active',
        'order_index',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'port' => 'integer',
        'order_index' => 'integer',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Build the standard Bardi ONVIF / RTSP stream URI.
     */
    public function getRtspUrlAttribute(): ?string
    {
        if (empty($this->ip_address)) {
            return null;
        }

        $port = $this->port ?: 554;
        $channel = $this->channel ?: 'ch0';
        $credentials = '';

        if (!empty($this->username)) {
            $user = urlencode($this->username);
            $pass = !empty($this->password) ? ':' . urlencode($this->password) : '';
            $credentials = "{$user}{$pass}@";
        }

        return "rtsp://{$credentials}{$this->ip_address}:{$port}/live/{$channel}";
    }

    /**
     * Get the playable streaming or iframe URL.
     */
    public function getPlayableUrlAttribute(): ?string
    {
        if (!empty($this->stream_url)) {
            return $this->stream_url;
        }

        return $this->rtsp_url;
    }
}

