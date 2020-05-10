<?php declare(strict_types = 1);

namespace App\Channels;

use Illuminate\Database\Eloquent\Collection;
use Jenssegers\Mongodb\Eloquent\Model;
use Illuminate\Support\Facades\Http;

class Channel extends Model
{
    protected $connection = 'mongodb';
    protected $guarded = ['_id'];

    public static function trackViewer($user_id, $views)
    {
        $channels = [];
        self::resetViewerLists($user_id);

        $views = self::validateViewerInput($views);
        foreach ($views as $view)
        {
            $host = $view['host'];
            $embed_id = $view['embed_id'];
            if ($host && $embed_id) {
                $channel = self::where('embed_id', $embed_id)->where('host', $host)->first();
                $first_viewer = !count($channel->viewers);
                $already_viewing = $channel->viewers[$user_id] ?? false;
                $channel->viewers = [
                    $user_id => true
                ];
                // $channel->statistics = array_merge($channel->statistics, [
                //     "session_start" => $first_viewer ? strtotime('now') : $channel->statistics->session_start,
                //     "lifetime_views" => !$already_viewing ? $channel->statistics->lifetime_views++ : $channel->statistics->lifetime_views,
                // ]);
                $channel->save();
                $channels[] = $channel;
            }
        }

        return [
            'user' => $user_id,
            'channels' => $channels
        ];
    }

    private static function validateViewerInput($views)
    {
        $output = [];
        $seen = [];
        $allowed_hosts = [
            'twitch',
            'youtube',
            'livestream',
            'niconico',
            'vaughnlive',
            'any'
        ];

        foreach ($views as $view)
        {
            if (!$view['embed_id']) {
                continue;
            }
            $host = strtolower($view['host']);
            $embed_id = ($host !== "youtube") ? strtolower($view['embed_id']) : $view['embed_id'];
            if (!in_array($host, $allowed_hosts) || array_key_exists("{$host}_{$embed_id}", $seen)) {
                continue;
            } else {
                $seen["{$host}_{$embed_id}"] = true;
                $output[] = [
                    'host' => $host,
                    'embed_id' => $embed_id
                ];
            }
        }

        return $output;
    }

    private static function resetViewerLists($user_id)
    {
        $search_key = "viewers.{$user_id}";
        $channels = self::where($search_key, true)->get();
        foreach ($channels as $channel)
        {
            $channel->viewers = self::unsetArrayKey($channel->viewers, $user_id);
            $last_viewer = !count($channel->viewers);
            // $channel->statistics = array_merge($channel->statistics, [
            //     "last_session_end" => $last_viewer ? strtotime('now') : $channel->statistics->last_session_end,
            // ]);

            $channel->save();
        }
        return $channels;
    }

    private static function unsetArrayKey(array $array, string $key)
    {
        return array_diff_key($array, array_flip([$key])); // wtf
    }

    /**
     * START: Legacy database migration
     */

    public static function importLegacyDatabase() : Collection
    {
        $response = Http::get('http://fightanvidya.com/4a4a/2019/api/export')->json();
        $data = self::normalizeLegacyData($response);
        self::truncate();
        foreach ($data as $d) 
        {
            $channel = new Channel($d);
            $channel->save();
        }
        return self::all();
    }

    private static function normalizeLegacyData(array $channels) : array
    {
        $output = [];
        
        foreach ($channels as $c) 
        {
            $output[] = [
                'host' => self::getCanonicalHostname($c['site']),
                'embed_id' => self::getEmbedId($c),
                'host_id' => self::getHostId($c),
                // 'live' => $c['live'],
                'live' => false,
                'legacy' => true,
                'temporary' => false,

                'display' => [
                    'label' => strip_tags($c['name']),
                    'alternate_label' => strip_tags($c['alt']),
                    'use_alternate_label' => false,
                    'label_color' => null,

                    'icon' => null,
                    'custom_icon' => $c['icon'],
                    'use_custom_icon' => false,
                    
                    'border_color' => null,
                    'use_border' => false,
                ],
                
                'viewers' => [],

                'statistics' => [
                    'session_start' => null,
                    'last_session_end' => null,
                    'lifetime_views' => null,
                    'lifetime_view_time' => null,
                    'score' => null,
                    'date_last_live' => null,
                ],       
                
                'groups' => [
                    'owner' => null,
                    'category' => $c['category'],
                    'priority' => $c['priority'],
                ],

                'host_data' => new \stdClass()
            ];
        }
        return $output;
    }

    private static function getCanonicalHostname(string $legacy_hostname) : ?string
    {
        $hostname_map = [
            'ttv' => 'twitch',
            'jtv' => 'twitch',
            'yut' => 'youtube',
            'lst' => 'livestream',
            'ust' => 'ustream',
            'nnd' => 'niconico',
            'nnl' => 'nicolive',
            'o3d' => 'own3d',
            'htv' => 'hashd',
            'vtv' => 'vaughnlive',
            'any' => 'any'
        ];
        return $hostname_map[$legacy_hostname] ?? null;
    }

    private static function getEmbedId(array $legacy_data) : string
    {
        $host = self::getCanonicalHostname($legacy_data['site']);
        switch ($host)
        {
            case 'youtube':
                return $legacy_data['chan'];
            default:
                return strtolower($legacy_data['chan']);
        }
    }

    private static function getHostId(array $legacy_data) : ?string
    {
        $host = self::getCanonicalHostname($legacy_data['site']);
        switch ($host)
        {
            case 'twitch':
                return $legacy_data['user_id'] ?: null;
            case 'youtube':
                return $legacy_data['alt'] ?: null;
            default:
                return null;
        }
    }

    /**
     * END: Legacy database migration
     */
}
