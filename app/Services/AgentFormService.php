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

        // Simulate API call delay
        sleep(2);

        // Simulate CPU usage
        UtilService::consume_cpu_with_hashing();

        // Simulate memory usage
        UtilService::consume_cpu_with_math();


        // Simulate failure 2 out of 3 times (66% failure rate)
        $shouldFail = rand(1, 3) <= 2;

        if ($shouldFail && $attempt < 3) {
            Log::warning("⚠️ AgentFormService: Email verification failed for AgentForm ID: {$agentForm->id} (Attempt: {$attempt}) - External service error");
            throw new \Exception("Email verification service temporarily unavailable - attempt {$attempt}");
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

        // Simulate email service API call delay
        sleep(1);

        // Simulate email service API call delay
        UtilService::consume_cpu_with_math();
        UtilService::consume_cpu_with_math();

        // Simulate failure 2 out of 3 times (66% failure rate)
        $shouldFail = rand(1, 3) <= 2;

        if ($shouldFail && $attempt < 3) {
            Log::warning("⚠️ AgentFormService: Welcome email sending failed for AgentForm ID: {$agentForm->id} (Attempt: {$attempt}) - Email service error");
            throw new \Exception("Email service temporarily unavailable - attempt {$attempt}");
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

        return [
            'total' => $total,
            'verified' => $verified,
            'email_sent' => $emailSent,
            'completed' => $completed,
            'verification_rate' => $total > 0 ? round(($verified / $total) * 100, 2) : 0,
            'completion_rate' => $total > 0 ? round(($completed / $total) * 100, 2) : 0,
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
