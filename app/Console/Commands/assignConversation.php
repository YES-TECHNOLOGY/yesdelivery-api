<?php

namespace App\Console\Commands;

use App\Http\Controllers\ConversationController;
use App\Http\Controllers\TripController;
use Illuminate\Console\Command;

class assignConversation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'conversation:assign';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command to assign conversation to a user';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        return ConversationController::assignConversation();
    }
}
