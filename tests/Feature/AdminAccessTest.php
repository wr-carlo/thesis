<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_dashboard(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'id_number' => 9999,
        ]);

        $response = $this->actingAs($admin)->get('/admin');
        $response->assertStatus(200);
    }

    public function test_non_admin_is_forbidden(): void
    {
        $user = User::factory()->create([
            'role' => 'student',
            'id_number' => 8888,
        ]);

        $response = $this->actingAs($user)->get('/admin');
        $response->assertStatus(403);
    }
}

