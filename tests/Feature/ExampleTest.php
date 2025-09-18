<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Test application redirects root to artworks page.
     */
    public function test_the_application_redirects_to_artworks(): void
    {
        $response = $this->get('/');

        $response->assertStatus(302);
        $response->assertRedirect(route('artworks.index'));
    }

    /**
     * Test artworks page loads successfully.
     */
    public function test_artworks_page_loads(): void
    {
        $response = $this->get('/artworks');

        $response->assertStatus(200);
        $response->assertSee('作品');
    }
}
