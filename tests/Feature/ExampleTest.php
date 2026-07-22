<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_the_application_shows_public_landing_page(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('Paket keberangkatan umroh');
    }
}
