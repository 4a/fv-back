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

    public static function test()
    {
        Channel::importLegacyDatabase();
        Twitch::updateUserData();
        Twitch::updateStreamData();
        return Twitch::where('live', true)->get();
    }

    public static function migrateLegacyDatabase()
    {
        return Channel::importLegacyDatabase();
    }
}
