<?php

namespace App\Console\Commands;

use App\Jobs\VerifyEmailJob;
use App\Jobs\SendWelcomeEmailJob;
use App\Models\AgentForm;
use App\Services\AgentFormService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Requeue Failed Forms Command
 *
 * This command helps recover from Horizon downtime or job failures by finding
 * forms that haven't been properly processed and requeuing their jobs.
 *
 * Common Usage Scenarios:
 *
 * 1. After Horizon Downtime:
 *    php artisan forms:requeue --older-than=2
 *
 * 2. Check what needs requeuing (dry run):
 *    php artisan forms:requeue --dry-run --verbose
 *
 * 3. Requeue only verification jobs:
 *    php artisan forms:requeue --verification-only --limit=50
 *
 * 4. Emergency recovery (skip confirmation):
 *    php artisan forms:requeue --force --older-than=6
 *
 * 5. Requeue only email jobs for verified forms:
 *    php artisan forms:requeue --email-only
 *
 * 6. Large batch processing:
 *    php artisan forms:requeue --limit=500 --older-than=24
 *
 * The command intelligently separates forms that need verification from
 * those that are verified but missing welcome emails, ensuring proper
 * job sequencing and avoiding duplicate processing.
 */
class RequeueFailedForms extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'forms:requeue
                            {--dry-run : Show what would be requeued without actually doing it}
                            {--older-than=1 : Only requeue forms older than X hours (default: 1)}
                            {--limit=100 : Maximum number of forms to requeue (default: 100)}
                            {--verification-only : Only requeue verification jobs}
                            {--email-only : Only requeue email jobs}
                            {--force : Skip confirmation prompt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Requeue jobs for forms that have not been verified or had emails sent (useful for Horizon recovery)';

    /**
     * Execute the console command.
     */
    public function handle(AgentFormService $agentFormService): int
    {
        $dryRun = $this->option('dry-run');
        $olderThanHours = (int) $this->option('older-than');
        $limit = (int) $this->option('limit');
        $verificationOnly = $this->option('verification-only');
        $emailOnly = $this->option('email-only');
        $force = $this->option('force');

        $this->info('🔍 Scanning for forms that need requeuing...');
        $this->newLine();

        // Get forms that need verification
        $needsVerification = $this->getFormsNeedingVerification($olderThanHours, $limit);

        // Get forms that need email (already verified but no email sent)
        $needsEmail = $this->getFormsNeedingEmail($olderThanHours, $limit);

        // Apply filters
        if ($verificationOnly) {
            $needsEmail = collect();
        }
        if ($emailOnly) {
            $needsVerification = collect();
        }

        // Show summary
        $this->displaySummary($needsVerification, $needsEmail, $olderThanHours);

        if ($needsVerification->isEmpty() && $needsEmail->isEmpty()) {
            $this->info('✅ No forms need requeuing. All forms are properly processed!');
            return Command::SUCCESS;
        }

        // Show detailed breakdown if requested
        if ($this->option('verbose')) {
            $this->showDetailedBreakdown($needsVerification, $needsEmail);
        }

        // Confirmation (unless dry-run or force)
        if (!$dryRun && !$force) {
            if (!$this->confirm('Do you want to proceed with requeuing these jobs?')) {
                $this->info('❌ Operation cancelled.');
                return Command::SUCCESS;
            }
        }

        if ($dryRun) {
            $this->warn('🔍 DRY RUN MODE - No jobs will actually be queued');
            $this->newLine();
        }

        // Process requeuing
        $results = $this->processRequeuing($needsVerification, $needsEmail, $dryRun);

        // Show results
        $this->displayResults($results, $dryRun);

        return Command::SUCCESS;
    }

    /**
     * Get forms that need verification (not verified yet)
     */
    private function getFormsNeedingVerification(int $olderThanHours, int $limit): \Illuminate\Support\Collection
    {
        return AgentForm::whereNull('email_verified_at')
            ->where('created_at', '<=', now()->subHours($olderThanHours))
            ->orderBy('created_at', 'asc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get forms that need email (verified but no email sent)
     */
    private function getFormsNeedingEmail(int $olderThanHours, int $limit): \Illuminate\Support\Collection
    {
        return AgentForm::whereNotNull('email_verified_at')
            ->whereNull('email_sent_at')
            ->where('created_at', '<=', now()->subHours($olderThanHours))
            ->orderBy('email_verified_at', 'asc')
            ->limit($limit)
            ->get();
    }

    /**
     * Display summary of what needs to be requeued
     */
    private function displaySummary($needsVerification, $needsEmail, int $olderThanHours): void
    {
        $this->info("📊 Requeue Summary (forms older than {$olderThanHours} hour(s)):");
        $this->table(
            ['Status', 'Count', 'Action'],
            [
                ['Needs Verification', $needsVerification->count(), 'Queue VerifyEmailJob'],
                ['Needs Email', $needsEmail->count(), 'Queue SendWelcomeEmailJob'],
                ['Total Jobs', $needsVerification->count() + $needsEmail->count(), 'Total to requeue'],
            ]
        );
        $this->newLine();
    }

    /**
     * Show detailed breakdown of forms
     */
    private function showDetailedBreakdown($needsVerification, $needsEmail): void
    {
        if ($needsVerification->isNotEmpty()) {
            $this->info('🔍 Forms Needing Verification:');
            $this->table(
                ['ID', 'Email', 'Created', 'Age (hours)'],
                $needsVerification->map(function ($form) {
                    return [
                        $form->id,
                        $form->email,
                        $form->created_at->format('Y-m-d H:i:s'),
                        round($form->created_at->diffInHours(now()), 1),
                    ];
                })->toArray()
            );
            $this->newLine();
        }

        if ($needsEmail->isNotEmpty()) {
            $this->info('📧 Forms Needing Email:');
            $this->table(
                ['ID', 'Email', 'Verified At', 'Age (hours)'],
                $needsEmail->map(function ($form) {
                    return [
                        $form->id,
                        $form->email,
                        $form->email_verified_at->format('Y-m-d H:i:s'),
                        round($form->email_verified_at->diffInHours(now()), 1),
                    ];
                })->toArray()
            );
            $this->newLine();
        }
    }

    /**
     * Process the actual requeuing
     */
    private function processRequeuing($needsVerification, $needsEmail, bool $dryRun): array
    {
        $results = [
            'verification_queued' => 0,
            'email_queued' => 0,
            'verification_errors' => 0,
            'email_errors' => 0,
        ];

        // Requeue verification jobs
        if ($needsVerification->isNotEmpty()) {
            $this->info('🔄 Requeuing verification jobs...');
            $progressBar = $this->output->createProgressBar($needsVerification->count());
            $progressBar->start();

            foreach ($needsVerification as $form) {
                try {
                    if (!$dryRun) {
                        VerifyEmailJob::dispatch($form);
                    }
                    $results['verification_queued']++;
                } catch (\Exception $e) {
                    $results['verification_errors']++;
                    $this->error("Failed to queue verification for form {$form->id}: " . $e->getMessage());
                }
                $progressBar->advance();
            }
            $progressBar->finish();
            $this->newLine();
        }

        // Requeue email jobs
        if ($needsEmail->isNotEmpty()) {
            $this->info('📧 Requeuing email jobs...');
            $progressBar = $this->output->createProgressBar($needsEmail->count());
            $progressBar->start();

            foreach ($needsEmail as $form) {
                try {
                    if (!$dryRun) {
                        SendWelcomeEmailJob::dispatch($form);
                    }
                    $results['email_queued']++;
                } catch (\Exception $e) {
                    $results['email_errors']++;
                    $this->error("Failed to queue email for form {$form->id}: " . $e->getMessage());
                }
                $progressBar->advance();
            }
            $progressBar->finish();
            $this->newLine();
        }

        return $results;
    }

    /**
     * Display the results of requeuing
     */
    private function displayResults(array $results, bool $dryRun): void
    {
        $action = $dryRun ? 'Would be queued' : 'Successfully queued';

        $this->info('📈 Requeue Results:');
        $this->table(
            ['Job Type', 'Queued', 'Errors'],
            [
                ['Verification Jobs', $results['verification_queued'], $results['verification_errors']],
                ['Email Jobs', $results['email_queued'], $results['email_errors']],
                ['Total', $results['verification_queued'] + $results['email_queued'], $results['verification_errors'] + $results['email_errors']],
            ]
        );

        $totalQueued = $results['verification_queued'] + $results['email_queued'];
        $totalErrors = $results['verification_errors'] + $results['email_errors'];

        if ($totalErrors === 0) {
            $this->info("✅ {$action} {$totalQueued} jobs successfully!");
        } else {
            $this->warn("⚠️  {$action} {$totalQueued} jobs with {$totalErrors} errors.");
        }

        if (!$dryRun && $totalQueued > 0) {
            $this->newLine();
            $this->info('💡 Next steps:');
            $this->line('   • Monitor queue processing: php artisan queue:work --verbose');
            $this->line('   • Check metrics: php artisan horizon:metrics');
            $this->line('   • View logs: tail -f storage/logs/laravel.log');
        }
    }
}
