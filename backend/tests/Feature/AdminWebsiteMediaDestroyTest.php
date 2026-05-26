<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WebsiteMedia;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminWebsiteMediaDestroyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
    }

    public function test_super_admin_can_delete_website_media_file_and_record(): void
    {
        Storage::fake('local');

        $media = WebsiteMedia::create([
            'uuid' => 'carousel-delete-test-uuid',
            'original_filename' => 'slide.jpg',
            'mime_type' => 'image/jpeg',
            'encrypted_path' => 'website_media/carousel-delete-test-uuid.enc',
            'size' => 128,
        ]);
        Storage::disk('local')->put($media->encrypted_path, 'encrypted-content');

        $user = User::factory()->create();
        $user->assignRole('super-admin');

        $this->actingAs($user)
            ->deleteJson(route('admin.website-media.destroy', ['uuid' => $media->uuid]))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('website_media', ['uuid' => $media->uuid]);
        Storage::disk('local')->assertMissing($media->encrypted_path);
    }

    public function test_non_super_admin_cannot_delete_website_media(): void
    {
        $media = WebsiteMedia::create([
            'uuid' => 'carousel-delete-forbidden',
            'original_filename' => 'slide.jpg',
            'mime_type' => 'image/jpeg',
            'encrypted_path' => 'website_media/carousel-delete-forbidden.enc',
            'size' => 128,
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->deleteJson(route('admin.website-media.destroy', ['uuid' => $media->uuid]))
            ->assertForbidden();

        $this->assertDatabaseHas('website_media', ['uuid' => $media->uuid]);
    }
}
