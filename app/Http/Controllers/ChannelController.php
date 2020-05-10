<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Channels\Channel;
use App\Channels\Twitch;
use App\Token;

class ChannelController extends Controller
{
    public static function trackViewer(Request $request)
    {
        $data = $request->all();
        $user_id = $request->ip() ?? null;
        $views = $data['views'] ?? null;
        if ($user_id && $views) {
            Channel::trackViewer(md5($user_id), $views); // TODO: is md5 fine?
        }
        return json_encode(self::getChannels());
    }

    public static function getChannels($host = null)
    {
        $output = [];
        $channels = Channel::all();
        foreach ($channels as $channel)
        {
            $output[$channel['id']] = $channel;
        }
        return $output;
    }

    public static function test()
    {
        // Channel::importLegacyDatabase();
        Twitch::updateUserData();
        Twitch::updateStreamData();
        return Twitch::where('live', true)->get();
    }

    public static function migrateLegacyDatabase()
    {
        return Channel::importLegacyDatabase();
    }
}
