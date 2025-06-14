<?php

namespace App\Http\Controllers;

use App\Models\AgentForm;
use App\Jobs\VerifyEmailJob;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class AgentFormController extends Controller
{
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'secret' => 'required|string|max:255',
            ]);

            $agentForm = AgentForm::create($validated);

            // Dispatch the verification job
            VerifyEmailJob::dispatch($agentForm)->onQueue('verification');

            return redirect()->route('agentform.public')->with('success', 'Form submitted successfully! Email verification is in progress.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Re-throw validation exceptions so they're handled properly by Inertia
            throw $e;
        } catch (\Exception $e) {
            return redirect()->route('agentform.public')->with('error', 'An error occurred while submitting the form. Please try again.');
        }
    }
}
