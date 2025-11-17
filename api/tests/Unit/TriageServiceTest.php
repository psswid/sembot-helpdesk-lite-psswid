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
}
