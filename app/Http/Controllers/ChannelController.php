<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Channels\Channel;
use App\Channels\Twitch;
use App\Token;

class ChannelController extends Controller
{
    public static function getChannels($host = null)
    {
        $channels = [];
        if ($host) {
            $channels = Channel::where('host', $host)->get();
        } else {
            $channels = Channel::all();
        }
        return $channels;
    }

    public static function fetchAllTwitchUserData()
    {
        $output = [];
        $ids = Twitch::all()
            ->map(function($channel) {
                return $channel['embed_id'];
            })
            ->toArray();
        $chunks = array_chunk($ids, 100);
        foreach ($chunks as $chunk) 
        {
            $output = array_merge($output, Twitch::getUserData($chunk));
        }
        return $output;
    }

    public static function migrateLegacyDatabase()
    {
        return Channel::importLegacyDatabase();
    }
}
