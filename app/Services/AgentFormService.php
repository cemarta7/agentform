<?php

namespace App\Services;

use App\Models\AgentForm;
use Illuminate\Support\Facades\Log;

class AgentFormService
{
    /**
     * Simulate email verification process
     *
     * @param AgentForm $agentForm
     * @param int $attempt
     * @return bool
     * @throws \Exception
     */
    public function verifyEmail(AgentForm $agentForm, int $attempt = 1): bool
    {
        Log::info("🔍 AgentFormService: Starting email verification for AgentForm ID: {$agentForm->id} (Attempt: {$attempt})");

        if (config('services.agentform.type') === 'time') {
            // Simulate API call delay
            sleep(config('services.agentform.wait_time'));
        }

        if (config('services.agentform.type') === 'cpu') {
            // Simulate CPU usage
            UtilService::consume_cpu_with_hashing();
            UtilService::consume_cpu_with_math();
        }

        if (config('services.agentform.failed')) {
        // Simulate failure 2 out of 3 times (66% failure rate)
            $shouldFail = rand(1, 3) <= 2;

            if ($shouldFail && $attempt < 3) {
                Log::warning("⚠️ AgentFormService: Email verification failed for AgentForm ID: {$agentForm->id} (Attempt: {$attempt}) - External service error");
                    throw new \Exception("Email verification service temporarily unavailable - attempt {$attempt}");
            }
        }

        // Success case
        Log::info("✅ AgentFormService: Email verification successful for AgentForm ID: {$agentForm->id} (Attempt: {$attempt})");

        // Update the email_verified_at timestamp
        $agentForm->update([
            'email_verified_at' => now()
        ]);

        return true;
    }

    /**
     * Simulate sending welcome email
     *
     * @param AgentForm $agentForm
     * @param int $attempt
     * @return bool
     * @throws \Exception
     */
    public function sendWelcomeEmail(AgentForm $agentForm, int $attempt = 1): bool
    {
        Log::info("📧 AgentFormService: Starting welcome email sending for AgentForm ID: {$agentForm->id} (Attempt: {$attempt})");

        if (config('services.agentform.type') === 'time') {
            // Simulate API call delay
            sleep(config('services.agentform.wait_time'));
        }

        if (config('services.agentform.type') === 'cpu') {
            // Simulate CPU usage
            UtilService::consume_cpu_with_hashing();
            UtilService::consume_cpu_with_math();
        }

        if (config('services.agentform.failed')) {
        // Simulate failure 2 out of 3 times (66% failure rate)
        $shouldFail = rand(1, 3) <= 2;

        if ($shouldFail && $attempt < 3) {
            Log::warning("⚠️ AgentFormService: Welcome email sending failed for AgentForm ID: {$agentForm->id} (Attempt: {$attempt}) - Email service error");
                throw new \Exception("Email service temporarily unavailable - attempt {$attempt}");
            }
        }

        // Success case
        Log::info("✅ AgentFormService: Welcome email sent successfully for AgentForm ID: {$agentForm->id} (Attempt: {$attempt})");

        // Update the email_sent_at timestamp
        $agentForm->update([
            'email_sent_at' => now()
        ]);

        Log::info("🎉 AgentFormService: Email process completed for AgentForm ID: {$agentForm->id} - Email: {$agentForm->email}");

        return true;
    }

    /**
     * Get AgentForm statistics
     *
     * @return array
     */
    public function getStatistics(): array
    {
        $total = AgentForm::count();
        $verified = AgentForm::whereNotNull('email_verified_at')->count();
        $emailSent = AgentForm::whereNotNull('email_sent_at')->count();
        $completed = AgentForm::whereNotNull('email_verified_at')
                              ->whereNotNull('email_sent_at')
                              ->count();

        $pendingVerification = AgentForm::whereNull('email_verified_at')->count();
        $pendingEmails = AgentForm::whereNotNull('email_verified_at')
                                 ->whereNull('email_sent_at')
                                 ->count();

        $verificationRate = $total > 0 ? round(($verified / $total) * 100, 2) : 0;
        $emailRate = $verified > 0 ? round(($emailSent / $verified) * 100, 2) : 0;
        $overallCompletionRate = $total > 0 ? round(($completed / $total) * 100, 2) : 0;

        return [
            'total_forms' => $total,
            'verified_forms' => $verified,
            'emails_sent' => $emailSent,
            'completed_forms' => $completed,
            'pending_verification' => $pendingVerification,
            'pending_emails' => $pendingEmails,
            'verification_success_rate' => $verificationRate,
            'email_success_rate' => $emailRate,
            'overall_completion_rate' => $overallCompletionRate,

            // Legacy fields for backward compatibility
            'total' => $total,
            'verified' => $verified,
            'email_sent' => $emailSent,
            'completed' => $completed,
            'verification_rate' => $verificationRate,
            'completion_rate' => $overallCompletionRate,
        ];
    }

    /**
     * Check if AgentForm processing is complete
     *
     * @param AgentForm $agentForm
     * @return bool
     */
    public function isProcessingComplete(AgentForm $agentForm): bool
    {
        return !is_null($agentForm->email_verified_at) && !is_null($agentForm->email_sent_at);
    }

    /**
     * Reset AgentForm processing status (for testing)
     *
     * @param AgentForm $agentForm
     * @return void
     */
    public function resetProcessingStatus(AgentForm $agentForm): void
    {
        $agentForm->update([
            'email_verified_at' => null,
            'email_sent_at' => null,
        ]);

        Log::info("🔄 AgentFormService: Reset processing status for AgentForm ID: {$agentForm->id}");
    }
}
