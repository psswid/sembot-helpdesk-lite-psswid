<?php

namespace Tests\Unit;

use App\Services\Triage\MockTriageClient;
use App\Services\Triage\OpenAiTriageClient;
use App\Services\Triage\TriageClient;
use Tests\TestCase;

class TriageBindingTest extends TestCase
{
    public function test_mock_driver_binds_mock_client(): void
    {
        config(['services.llm.driver' => 'mock']);
        $client = app(TriageClient::class);
        $this->assertInstanceOf(MockTriageClient::class, $client);
    }

    public function test_openai_driver_binds_openai_client(): void
    {
        config(['services.llm.driver' => 'openai']);
        $client = app(TriageClient::class);
        $this->assertInstanceOf(OpenAiTriageClient::class, $client);
    }
}
