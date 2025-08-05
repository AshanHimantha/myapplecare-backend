<?php

namespace App\Console\Commands;

use App\Models\TicketItem;
use App\Models\Part;
use App\Models\Repair;
use Illuminate\Console\Command;

class UpdateTicketItemPrices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ticket-items:update-prices {--dry-run : Show what would be updated without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update all ticket items with current prices from parts and repairs tables';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        if ($dryRun) {
            $this->info('🔍 DRY RUN MODE - No changes will be made');
            $this->info('-------------------------------------------');
        } else {
            $this->info('🚀 Starting ticket item price update...');
            $this->info('-------------------------------------------');
        }

        // Get all ticket items
        $ticketItems = TicketItem::with(['part', 'repair'])->get();
        
        if ($ticketItems->isEmpty()) {
            $this->warn('No ticket items found in the database.');
            return;
        }

        $this->info("Found {$ticketItems->count()} ticket items to process");
        $this->newLine();

        $updatedCount = 0;
        $skippedCount = 0;
        $errorCount = 0;

        // Create progress bar
        $progressBar = $this->output->createProgressBar($ticketItems->count());
        $progressBar->start();

        foreach ($ticketItems as $ticketItem) {
            try {
                $soldPrice = null;
                $cost = null;
                $updated = false;

                if ($ticketItem->type === TicketItem::TYPE_PART && $ticketItem->part_id) {
                    // Update part prices
                    $part = Part::find($ticketItem->part_id);
                    if ($part) {
                        $soldPrice = $part->selling_price;
                        $cost = $part->unit_price;
                        
                        if (!$dryRun) {
                            $ticketItem->update([
                                'sold_price' => $soldPrice,
                                'cost' => $cost
                            ]);
                        }
                        $updated = true;
                    }
                } elseif ($ticketItem->type === TicketItem::TYPE_REPAIR && $ticketItem->repair_id) {
                    // Update repair prices
                    $repair = Repair::find($ticketItem->repair_id);
                    if ($repair) {
                        $soldPrice = $repair->selling_price ?? $repair->cost;
                        $cost = 0; // Repairs typically don't have cost (pure profit)
                        
                        if (!$dryRun) {
                            $ticketItem->update([
                                'sold_price' => $soldPrice,
                                'cost' => $cost
                            ]);
                        }
                        $updated = true;
                    }
                }

                if ($updated) {
                    $updatedCount++;
                    if ($dryRun) {
                        $this->line("\n✅ Would update Ticket Item #{$ticketItem->id} ({$ticketItem->type}):");
                        $this->line("   Current: sold_price=" . ($ticketItem->sold_price ?? 'null') . ", cost=" . ($ticketItem->cost ?? 'null'));
                        $this->line("   New:     sold_price={$soldPrice}, cost={$cost}");
                    }
                } else {
                    $skippedCount++;
                    if ($dryRun) {
                        $this->line("\n⚠️  Would skip Ticket Item #{$ticketItem->id} (no related part/repair found)");
                    }
                }

            } catch (\Exception $e) {
                $errorCount++;
                $this->error("\n❌ Error updating Ticket Item #{$ticketItem->id}: " . $e->getMessage());
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Show summary
        $this->info('📊 Summary:');
        $this->info("-------------------------------------------");
        $this->info("✅ Updated: {$updatedCount}");
        $this->info("⚠️  Skipped: {$skippedCount}");
        $this->info("❌ Errors:  {$errorCount}");
        $this->info("📋 Total:   {$ticketItems->count()}");

        if ($dryRun) {
            $this->newLine();
            $this->info('🔍 This was a dry run. No changes were made.');
            $this->info('Run without --dry-run flag to apply changes:');
            $this->comment('php artisan ticket-items:update-prices');
        } else {
            $this->newLine();
            $this->info('🎉 Price update completed successfully!');
        }

        return 0;
    }
}
