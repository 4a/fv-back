<?php declare(strict_types = 1);

namespace App\Channels;

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

    public static function getUserData(array $embed_ids) : array
    {
        $output = [];
        $token = self::getToken();
        if ($token) {
            $query = implode("&login=", $embed_ids);
            $response = Http::withHeaders([
                "Client-ID" => env('TWITCH_CLIENTID')
            ])->get("https://api.twitch.tv/helix/users?login={$query}");
            $output = $response->json()['data'];
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

    private static function validateToken($token)
    {
        $valid = false;
        $day = 86400; // seconds
        $response = Http::withHeaders([
            "Authorization" => "OAuth {$token}"
        ])->get("https://id.twitch.tv/oauth2/validate");
        if ($response->status() === 200) {
            $data = $response->json();
            $valid = !($data['expires_in'] < $day);
        }
        return $valid;
    }
}
