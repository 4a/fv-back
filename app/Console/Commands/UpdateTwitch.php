<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Channels\Twitch;

class UpdateTwitch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:twitch';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get the latest user and stream data from twitch servers';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        Twitch::updateUserData(); // get the latest user data from twitch
        Twitch::updateStreamData(); // get the latest stream data from twitch
        echo "twitch channels updated";
    }
}
