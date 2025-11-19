<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Ticket;
use App\Models\User;
use App\Services\Triage\TriageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class TriageServiceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function mock_driver_returns_expected_shape(): void
    {
        config(['services.llm.driver' => 'mock']);

        $user = User::factory()->create();
        $ticket = Ticket::factory()->create([
            'reporter_id' => $user->id,
            'title' => 'App crash on payment',
            'description' => 'User reports crash and urgent error during billing payment flow',
        ]);

        $service = app(TriageService::class);
        $suggestion = $service->suggestFor($ticket, $user);

        $this->assertIsArray($suggestion);
        $this->assertArrayHasKey('priority', $suggestion);
        $this->assertArrayHasKey('tags', $suggestion);
        $this->assertArrayHasKey('reasoning', $suggestion);
        $this->assertArrayHasKey('confidence', $suggestion);
        $this->assertEquals('high', $suggestion['priority']); // crash/urgent/error keywords
        $this->assertContains('crash', $suggestion['tags']);
        $this->assertContains('error', $suggestion['tags']);
        $this->assertContains('billing', $suggestion['tags']);
        $this->assertTrue($suggestion['confidence'] >= 0.55);
    }

    #[Test]
    public function when_description_is_missing_defaults_are_returned(): void
    {
        config(['services.llm.driver' => 'mock']);

        $user = User::factory()->create();
        $ticket = Ticket::factory()->create([
            'reporter_id' => $user->id,
            'title' => 'Minor UI issue',
            'description' => null,
        ]);

        $service = app(TriageService::class);
        $suggestion = $service->suggestFor($ticket, $user);

        $this->assertIsArray($suggestion);
        $this->assertArrayHasKey('priority', $suggestion);
        $this->assertArrayHasKey('tags', $suggestion);
        $this->assertArrayHasKey('reasoning', $suggestion);

        // With only a short title and no description, mock heuristics should
        // fall back to general tagging / low priority when no strong keywords.
        $this->assertEquals('low', $suggestion['priority']);
        $this->assertContains('general', $suggestion['tags']);
        $this->assertStringContainsString('defaulting to general', strtolower($suggestion['reasoning']));
    }

    #[Test]
    public function malformed_driver_response_is_normalized(): void
    {
        // Bind a fake client that returns malformed values to test normalization.
        $this->app->bind(\App\Services\Triage\TriageClient::class, function () {
            return new class implements \App\Services\Triage\TriageClient {
                public function suggest(\App\Models\Ticket $ticket, \App\Models\User $user): array
                {
                    return [
                        'priority' => 'urgent', // invalid
                        'tags' => 'not-an-array', // invalid
                        'assignee_hint' => 'someone',
                        'reasoning' => 'malformed response',
                        'confidence' => 2.5, // out of range
                        'driver' => 'fake-stub',
                    ];
                }
            };
        });

        $user = User::factory()->create();
        $ticket = Ticket::factory()->create([
            'reporter_id' => $user->id,
            'title' => 'Stub ticket',
            'description' => 'irrelevant',
        ]);

        $service = app(TriageService::class);
        $suggestion = $service->suggestFor($ticket, $user);

        // priority 'urgent' should be sanitized to 'medium'
        $this->assertEquals('medium', $suggestion['priority']);

        // tags should be normalized to an array (fallback to 'general')
        $this->assertIsArray($suggestion['tags']);
        $this->assertContains('general', $suggestion['tags']);

        // confidence must be clamped into 0..1
        $this->assertGreaterThanOrEqual(0.0, $suggestion['confidence']);
        $this->assertLessThanOrEqual(1.0, $suggestion['confidence']);

        // driver should be preserved
        $this->assertEquals('fake-stub', $suggestion['driver']);
    }
}
