<?php

namespace App;

use Jenssegers\Mongodb\Eloquent\Model;

class Token extends Model
{
    protected $connection = 'mongodb';
    protected $guarded = ['_id'];

    public static function store($type, $data)
    {
        $token = self::where('type', $type)->first() ?? new Token();
        $token->type = $type;
        $token->access_token = $data['access_token'];
        $token->expires_in = $data['expires_in'];
        $token->save();
        return $token;
    }
}
