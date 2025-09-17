<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ListUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:list-users';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all users in the database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $users = \App\Models\User::all(['id', 'name', 'email']);
        
        $this->info('Users in database:');
        foreach ($users as $user) {
            $this->line($user->id . ' - ' . $user->name . ' - ' . $user->email);
        }
        
        $this->info('Total users: ' . $users->count());
    }
}
