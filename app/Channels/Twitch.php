<?php declare(strict_types = 1);

namespace App\Channels;

use Illuminate\Database\Eloquent\Collection;
use Jenssegers\Mongodb\Eloquent\Model;
use Illuminate\Support\Facades\Http;

use App\Token;

class Twitch extends Channel
{
    protected $table = 'channels';

    public static function boot() : void
    {
        parent::boot();

        static::addGlobalScope(function ($query) {
            $query->where('host', 'twitch');
        });
    }

    public static function updateStreamData() : void // update twitch rows in "channels"
    {
        $channels = self::whereNotNull('host_id')->get();
        $ids = $channels->map(function($channel) {
                return $channel['host_id'];
            })->toArray();
        $chunks = array_chunk($ids, 100);

        $response = [];
        foreach ($chunks as $chunk) 
        {
            $response = array_merge($response, Twitch::getStreamData($chunk));
        }

        self::query()->update(['live' => false]);
        foreach ($response as $stream_data)
        {
            $channel = Twitch::where('host_id', $stream_data['user_id'])->first();
            if ($channel) {
                $channel->live = true;
                $channel->host_data = array_merge($channel->host_data, [
                    'stream' => $stream_data
                ]);
                $channel->save();
            }
        }
    }

    public static function updateUserData() : void // update twitch rows in "channels"
    {
        $channels = self::all();
        $ids = $channels->map(function($channel) {
                return strtolower($channel['embed_id']);
            })->toArray();
        $chunks = array_chunk($ids, 100);

        $response = [];
        foreach ($chunks as $chunk) 
        {
            $response = array_merge($response, Twitch::getUserData($chunk));
        }

        foreach ($response as $user_data)
        {
            $channel = Twitch::where('embed_id', $user_data['login'])->first();
            if ($channel) {
                $channel->host_id = $user_data['id'];
                $channel->display = array_merge($channel->display, [
                    'icon' => $user_data['profile_image_url']
                ]);
                $channel->host_data = array_merge($channel->host_data, [
                    'user' => $user_data
                ]);
                $channel->save();
            }
        }
    }

    public static function getStreamData(array $user_ids) : array // https://dev.twitch.tv/docs/api/reference#get-streams
    {
        $output = [];
        $token = self::getToken();
        if ($token) {
            $query = implode("&user_id=", $user_ids);
            $response = Http::withHeaders([
                "Client-ID" => env('TWITCH_CLIENTID'),
                "Authorization" => "Bearer {$token}"
            ])->get("https://api.twitch.tv/helix/streams?user_id={$query}");
            if ($response->status() === 200) {
                $output = $response->json()['data'];
            }
        }
        return $output;
    }

    public static function getUserData(array $embed_ids) : array // https://dev.twitch.tv/docs/api/reference#get-users
    {
        $output = [];
        $token = self::getToken();
        if ($token) {
            $query = implode("&login=", $embed_ids);
            $response = Http::withHeaders([
                "Client-ID" => env('TWITCH_CLIENTID'),
                "Authorization" => "Bearer {$token}"
            ])->get("https://api.twitch.tv/helix/users?login={$query}");
            if ($response->status() === 200) {
                $output = $response->json()['data'];
            }
        }
        return $output;
    }

    private static function getToken() : ?string
    {
        $cache = Token::where('type', 'twitch')->first();
        if (!$cache || !self::validateToken($cache->access_token)) {
            $client_id = env('TWITCH_CLIENTID');
            $client_secret = env('TWITCH_CLIENTSECRET');
            $response = Http::post("https://id.twitch.tv/oauth2/token", [
                'client_id' => $client_id,
                'client_secret' => $client_secret,
                'grant_type' => 'client_credentials',
                'scope' => '' // https://dev.twitch.tv/docs/authentication#scopes
            ]);
            if ($response->status() === 200) {
                $cache = Token::store('twitch', $response->json());
            }
        }
        return $cache->access_token ?? null;
    }

    private static function validateToken(string $access_token) : bool
    {
        $valid = false;
        $day = 86400; // seconds
        $response = Http::withHeaders([
            "Authorization" => "OAuth {$access_token}"
        ])->get("https://id.twitch.tv/oauth2/validate");
        if ($response->status() === 200) {
            $data = $response->json();
            $valid = !($data['expires_in'] < $day);
        }
        return $valid;
    }
}
