<?php

namespace Tests\Feature;

use App\Models\AiGeneratedImage;
use App\Models\Company;
use App\Models\User;
use App\Models\WebsiteMedia;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminAiImagesPageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
    }

    #[Test]
    public function delete_confirm_modal_is_a_centered_overlay_with_blur(): void
    {
        $company = Company::query()->create(['name' => 'AI Images Co', 'is_active' => true]);
        $admin = User::factory()->create(['company_id' => null]);
        $admin->assignRole('super-admin');

        $this->actingAs($admin)
            ->withSession(['selected_tenant' => $company->id])
            ->get(route('admin.ai-images.index'))
            ->assertOk()
            ->assertSee('id="ai-image-confirm"', false)
            ->assertSee('Deze afbeelding definitief verwijderen?', false)
            ->assertSee('bg-slate-900/45 backdrop-blur-md', false)
            ->assertSee('ai-image-confirm__dialog relative z-10 w-full max-w-sm', false)
            ->assertDontSee('ai-image-confirm__dialog kt-card', false);
    }

    #[Test]
    public function gallery_has_reuse_action_and_source_chip(): void
    {
        Storage::fake('local');
        $admin = $this->superAdmin();
        $company = Company::query()->create(['name' => 'AI Images Co', 'is_active' => true]);
        $this->createGeneratedImage($admin, 'Geel taxi-logo');

        $this->actingAs($admin)
            ->withSession(['selected_tenant' => $company->id])
            ->get(route('admin.ai-images.index'))
            ->assertOk()
            ->assertSee('data-action="reuse"', false)
            ->assertSee('Hergebruiken als bron', false)
            ->assertSee('id="ai-image-source"', false)
            ->assertSee('Bron voor aanpassing', false)
            ->assertSee('id="ai-image-prompt-clear"', false)
            ->assertSee('ki-eraser ki-duotone', false)
            ->assertSee('data-tooltip="Wist de omschrijving in één keer"', false)
            ->assertSee('Tekst wissen', false);
    }

    #[Test]
    public function generate_without_source_calls_openai_generations(): void
    {
        Storage::fake('local');
        config(['services.openai.api_key' => 'sk-test-ai-images']);
        $admin = $this->superAdmin();

        Http::fake([
            'https://api.openai.com/v1/images/generations' => Http::response([
                'data' => [['b64_json' => base64_encode('new-png-bytes')]],
            ], 200),
        ]);

        $this->actingAs($admin)
            ->postJson(route('admin.ai-images.generate'), [
                'prompt' => 'Een blauwe taxi in de stad',
            ])
            ->assertOk()
            ->assertJsonPath('prompt', 'Een blauwe taxi in de stad');

        Http::assertSent(fn ($request) => $request->url() === 'https://api.openai.com/v1/images/generations');
        Http::assertNotSent(fn ($request) => str_contains($request->url(), '/images/edits'));
        $this->assertSame(1, AiGeneratedImage::query()->count());
    }

    #[Test]
    public function generate_with_source_sends_image_to_openai_edits(): void
    {
        Storage::fake('local');
        config(['services.openai.api_key' => 'sk-test-ai-images']);
        $admin = $this->superAdmin();
        $source = $this->createGeneratedImage($admin, 'Geel taxi-logo');

        Http::fake([
            'https://api.openai.com/v1/images/edits' => Http::response([
                'data' => [['b64_json' => base64_encode('edited-png-bytes')]],
            ], 200),
        ]);

        $this->actingAs($admin)
            ->postJson(route('admin.ai-images.generate'), [
                'prompt' => 'Zelfde logo, maar in blauw',
                'source_uuid' => $source->website_media_uuid,
            ])
            ->assertOk()
            ->assertJsonPath('prompt', 'Zelfde logo, maar in blauw');

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.openai.com/v1/images/edits'
                && str_contains($request->body(), 'Zelfde logo, maar in blauw');
        });
        Http::assertNotSent(fn ($request) => str_contains($request->url(), '/images/generations'));
        $this->assertSame(2, AiGeneratedImage::query()->count());
    }

    private function superAdmin(): User
    {
        $user = User::factory()->create(['company_id' => null]);
        $user->assignRole('super-admin');

        return $user;
    }

    private function createGeneratedImage(User $user, string $prompt): AiGeneratedImage
    {
        $uuid = (string) Str::uuid();
        $path = 'website_media/'.$uuid.'.enc';
        Storage::disk('local')->put($path, Crypt::encrypt('source-png-bytes'));

        WebsiteMedia::query()->create([
            'uuid' => $uuid,
            'original_filename' => 'logo.png',
            'mime_type' => 'image/png',
            'encrypted_path' => $path,
            'size' => 16,
        ]);

        return AiGeneratedImage::query()->create([
            'website_media_uuid' => $uuid,
            'prompt' => $prompt,
            'created_by' => $user->id,
        ]);
    }
}
