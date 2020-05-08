<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Channel;

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

    public static function initialize()
    {
        return Channel::importLegacyDatabase();
    }
}
