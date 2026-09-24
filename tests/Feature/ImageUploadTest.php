<?php

namespace Tests\Feature;

use App\Models\Image;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_image_upload_requires_a_base64_photo_array(): void
    {
        Storage::fake('public');
        $token = $this->user()->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/images', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('photos');
    }

    public function test_invalid_base64_is_rejected_without_creating_records(): void
    {
        Storage::fake('public');
        $token = $this->user()->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/images', ['photos' => ['not-base64-']])
            ->assertStatus(422)
            ->assertJsonPath('errors.photos.0', 'Invalid image file.');

        $this->assertSame(0, Image::query()->count());
    }

    private function user()
    {
        return User::factory()->create();
    }
}
