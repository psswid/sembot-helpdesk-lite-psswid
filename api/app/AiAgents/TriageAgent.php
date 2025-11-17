<?php

namespace App\AiAgents;

use LarAgent\Agent;

/**
 * LarAgent-based AI agent for generating ticket triage suggestions.
 * Uses the configured model from services.llm.model.
 */
class TriageAgent extends Agent
{
    /**
     * Dynamically resolve model from configuration allowing runtime changes.
     */
    protected string $model;

    public function __construct()
    {
        $this->model = (string) config('services.llm.model', 'gpt-4o-mini');
    }
}
