<?php

namespace App;

use Jenssegers\Mongodb\Eloquent\Model;
use Illuminate\Support\Facades\Http;

class Channel extends Model
{
    protected $connection = 'mongodb';
    protected $guarded = ['_id'];

    /**
     * START: Database initialization
     */

    public static function importLegacyDatabase()
    {
        $response = Http::get('http://fightanvidya.com/4a4a/2019/api/export');
        $data = self::normalizeLegacyData($response);
        self::truncate();
        foreach ($data as $d) 
        {
            $channel = new Channel($d);
            $channel->save();
        }
        return self::all();
    }

    private static function normalizeLegacyData($response)
    {
        $output = [];
        $channels = json_decode($response, true);
        
        foreach ($channels as $c) 
        {
            $output[] = [
                'host' => self::getCanonicalHostname($c['site']),
                'embed_id' => $c['chan'],
                'host_id' => self::getHostId($c),
                'live' => $c['live'],

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
                
                'statistics' => [
                    'popularity' => null,
                    'session_start' => null,
                    'last_session_end' => null,
                    'views' => null,
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
            ];
        }
        return $output;
    }

    private static function getCanonicalHostname($legacy_hostname)
    {
        $hostname_map = [
            'ttv' => 'twitch',
            'jtv' => 'twitch',
            'yut' => 'youtube',
            'lst' => 'livestream',
            'ust' => 'ustream',
            'nnd' => 'niconico',
            'nnl' => 'niconico',
            'o3d' => 'own3d',
            'htv' => 'hashd',
            'vtv' => 'vaughnlive',
            'any' => 'any'
        ];
        return $hostname_map[$legacy_hostname];
    }

    private static function getHostId($legacy_data)
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
     * END: Database initialization
     */
}
