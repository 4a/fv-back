<?php declare(strict_types = 1);

namespace App;

use Illuminate\Database\Eloquent\Collection;
use Jenssegers\Mongodb\Eloquent\Model;

class Token extends Model
{
    protected $connection = 'mongodb';
    protected $guarded = ['_id'];

    public static function store(string $type, array $data) : Token
    {
        $token = self::where('type', $type)->first() ?? new Token();
        $token->type = $type;
        $token->access_token = $data['access_token'];
        $token->expires_in = $data['expires_in'];
        $token->save();
        return $token;
    }
}
