<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ProcessDailyRewards extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rewards:process-daily';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process daily rewards like birthdays and anniversaries';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = now();

        $this->info('Processing daily rewards for ' . $today->toDateString());

        // Process Birthdays
        $birthdayUsers = \App\Models\User::whereMonth('birth_date', $today->month)
            ->whereDay('birth_date', $today->day)
            ->get();
        
        $this->info("Found {$birthdayUsers->count()} users with a birthday today.");
        
        foreach ($birthdayUsers as $user) {
            event(new \App\Events\CustomerBirthday($user));
        }

        // Process Anniversaries
        $anniversaryUsers = \App\Models\User::whereMonth('created_at', $today->month)
            ->whereDay('created_at', $today->day)
            ->whereYear('created_at', '<', $today->year) // Must be at least 1 year old
            ->get();
        
        $this->info("Found {$anniversaryUsers->count()} users with an anniversary today.");
        
        foreach ($anniversaryUsers as $user) {
            event(new \App\Events\CustomerAnniversary($user));
        }

        $this->info('Finished processing daily rewards.');
    }
}
