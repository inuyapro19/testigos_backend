<?php

namespace App\Console\Commands;

use App\Models\Investment;
use Illuminate\Console\Command;

class UpdateActualReturnPercentages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'investments:update-return-percentages';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update actual_return_percentage for existing investments with actual_return';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Updating actual_return_percentage for investments...');

        // Get all investments that have actual_return but no actual_return_percentage
        $investments = Investment::whereNotNull('actual_return')
            ->whereNull('actual_return_percentage')
            ->get();

        $this->info("Found {$investments->count()} investments to update.");

        $updated = 0;
        foreach ($investments as $investment) {
            if ($investment->amount > 0) {
                $actualReturnPercentage = round(
                    (($investment->actual_return - $investment->amount) / $investment->amount) * 100,
                    2
                );

                $investment->update([
                    'actual_return_percentage' => $actualReturnPercentage
                ]);

                $updated++;
            }
        }

        $this->info("Successfully updated {$updated} investments.");

        return Command::SUCCESS;
    }
}
