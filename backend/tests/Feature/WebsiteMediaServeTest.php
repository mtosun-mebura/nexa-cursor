<?php

namespace Tests\Feature;

use App\Models\WebsiteMedia;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WebsiteMediaServeTest extends TestCase
{
    use RefreshDatabase;

    public function test_serve_returns_decrypted_image_with_correct_mime_type(): void
    {
        Storage::fake('local');

        $jpeg = base64_decode('/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////2wBDAf//////////////////////////////////////////////////////////////////////////////////////wAARCAABAAEDAREAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAb/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFQEBAQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIRAxEAPwCdABmX/9k=');
        $encrypted = Crypt::encrypt($jpeg);
        $uuid = 'carousel-serve-test-uuid';

        $media = WebsiteMedia::create([
            'uuid' => $uuid,
            'original_filename' => 'slide.jpg',
            'mime_type' => 'application/octet-stream',
            'encrypted_path' => 'website_media/'.$uuid.'.enc',
            'size' => strlen($jpeg),
        ]);
        Storage::disk('local')->put($media->encrypted_path, $encrypted);

        $response = $this->get(route('website-media.serve', ['uuid' => $uuid]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/jpeg');
        $this->assertSame($jpeg, $response->getContent());
    }
}
