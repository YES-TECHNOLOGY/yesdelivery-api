<?php

namespace App\Console\Commands;

use App\Http\Controllers\ConversationController;
use Illuminate\Console\Command;

class checkConversations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'conversations:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        return ConversationController::checkConversations();
    }
}
