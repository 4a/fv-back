<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Channels\Channel;

class MigrateLegacyDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'legacydb:migrate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import the database from the old server';

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
        Channel::importLegacyDatabase();
    }
}
