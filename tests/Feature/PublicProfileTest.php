<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_public_profile_with_name_only(): void
    {
        // Plaintext password: the factory's stock hash was built with a
        // different bcrypt cost than phpunit.xml forces, so it fails the
        // `hashed` cast verification in this environment.
        $user = User::factory()->create(['password' => 'secret123']);

        $response = $this->actingAs($user)->postJson('/api/users/public-profiles', [
            'name' => 'Adaeze Okafor',
        ]);

        $response->assertStatus(201);
        $data = $response->json('data');
        $this->assertEquals('Adaeze Okafor', $data['name']);
        $this->assertTrue((bool) $data['is_public_profile']);

        $this->assertDatabaseHas('users', [
            'id' => $data['id'],
            'name' => 'Adaeze Okafor',
            'is_public_profile' => true,
            'created_by' => $user->id,
        ]);
    }

    public function test_creates_public_profile_with_avatar_upload(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['password' => 'secret123']);

        $file = UploadedFile::fake()->image('avatar.jpg');

        $response = $this->actingAs($user)->post('/api/users/public-profiles', [
            'name' => 'John Mensah',
            'avatar' => $file,
        ]);

        $response->assertStatus(201);
        $avatar = $response->json('data.avatar');
        $this->assertNotEmpty($avatar);
        $this->assertStringContainsString('avatars/', $avatar);
    }

    public function test_requires_name(): void
    {
        $user = User::factory()->create(['password' => 'secret123']);

        $response = $this->actingAs($user)->postJson('/api/users/public-profiles', [
            'name' => '',
        ]);

        $response->assertStatus(422);
    }
}
