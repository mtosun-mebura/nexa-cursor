<?php

namespace App\Modules\NexaTaxi\Services;

use App\Models\User;
use App\Modules\NexaTaxi\Models\TaxiDriverProfile;
use App\Modules\NexaTaxi\Support\TaxiDriverProfileSchema;
use App\Services\ModuleDatabaseService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class TaxiDriverProfileMediaService
{
    public function __construct(
        protected ModuleDatabaseService $moduleDb
    ) {}

    public function connection(): string
    {
        return $this->moduleDb->getModuleConnectionName('taxi');
    }

    public function ensureSchema(): void
    {
        TaxiDriverProfileSchema::ensure($this->connection());
    }

    public function profile(int $driverId): ?TaxiDriverProfile
    {
        $this->ensureSchema();

        return TaxiDriverProfile::on($this->connection())->find($driverId);
    }

    public function firstOrCreate(int $driverId): TaxiDriverProfile
    {
        $this->ensureSchema();
        $conn = $this->connection();

        $row = TaxiDriverProfile::on($conn)->find($driverId);
        if ($row) {
            return $row;
        }

        $row = new TaxiDriverProfile(['driver_id' => $driverId]);
        $row->setConnection($conn);
        $row->save();

        return $row;
    }

    public function hasOwnProfilePhoto(User $driver): bool
    {
        return ! empty($driver->photo_blob);
    }

    public function assertCanAcceptRides(User $driver): void
    {
        if (! $this->hasOwnProfilePhoto($driver)) {
            throw ValidationException::withMessages([
                'profile_photo' => ['Voeg eerst een profielfoto van jezelf toe in Profiel voordat je een rit kunt accepteren.'],
            ]);
        }
    }

    /**
     * @return array{
     *     profile_photo_url: ?string,
     *     company_logo_url: ?string,
     *     vehicle_photo_url: ?string,
     *     has_profile_photo: bool,
     *     rating_avg: ?float,
     *     rating_count: int
     * }
     */
    public function publicPayload(User $driver): array
    {
        $profile = $this->profile((int) $driver->id);
        $hasPhoto = $this->hasOwnProfilePhoto($driver);

        return [
            'profile_photo_url' => $hasPhoto ? url('/secure-photo/'.$driver->getPhotoToken()) : null,
            'company_logo_url' => $this->publicUrl($profile?->company_logo_path),
            'vehicle_photo_url' => $this->publicUrl($profile?->vehicle_photo_path),
            'has_profile_photo' => $hasPhoto,
            'rating_avg' => $profile && $profile->rating_count > 0 ? (float) $profile->rating_avg : null,
            'rating_count' => (int) ($profile->rating_count ?? 0),
        ];
    }

    public function storeProfilePhoto(User $driver, UploadedFile $file): array
    {
        $this->validateImage($file);
        $content = file_get_contents($file->getRealPath());
        if ($content === false) {
            throw ValidationException::withMessages(['photo' => ['Upload mislukt.']]);
        }

        $driver->photo_blob = base64_encode($content);
        $driver->photo_mime_type = $file->getMimeType() ?: 'image/jpeg';
        $driver->photo = 'driver-profile';
        $driver->save();

        return $this->publicPayload($driver->fresh() ?? $driver);
    }

    public function storeCompanyLogo(User $driver, UploadedFile $file): array
    {
        $path = $this->storeFile((int) $driver->id, 'company_logo', $file);
        $row = $this->firstOrCreate((int) $driver->id);
        if ($row->company_logo_path && $row->company_logo_path !== $path) {
            Storage::disk('public')->delete($row->company_logo_path);
        }
        $row->company_logo_path = $path;
        $row->save();

        return $this->publicPayload($driver);
    }

    public function storeVehiclePhoto(User $driver, UploadedFile $file): array
    {
        $path = $this->storeFile((int) $driver->id, 'vehicle', $file);
        $row = $this->firstOrCreate((int) $driver->id);
        if ($row->vehicle_photo_path && $row->vehicle_photo_path !== $path) {
            Storage::disk('public')->delete($row->vehicle_photo_path);
        }
        $row->vehicle_photo_path = $path;
        $row->save();

        return $this->publicPayload($driver);
    }

    public function applyRating(int $driverId, int $stars): void
    {
        $this->ensureSchema();
        $row = $this->firstOrCreate($driverId);
        $row->rating_count = (int) $row->rating_count + 1;
        $row->rating_sum = (int) $row->rating_sum + $stars;
        $row->rating_avg = round($row->rating_sum / max(1, $row->rating_count), 2);
        $row->save();
    }

    protected function storeFile(int $driverId, string $kind, UploadedFile $file): string
    {
        $this->validateImage($file);
        $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        if (! in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            $ext = 'jpg';
        }
        $dir = 'taxi-driver-media/'.$driverId;
        $name = $kind.'-'.time().'.'.$ext;

        return $file->storeAs($dir, $name, 'public');
    }

    protected function validateImage(UploadedFile $file): void
    {
        if (! str_starts_with((string) $file->getMimeType(), 'image/')) {
            throw ValidationException::withMessages(['photo' => ['Alleen afbeeldingen zijn toegestaan.']]);
        }
        if ($file->getSize() > 5 * 1024 * 1024) {
            throw ValidationException::withMessages(['photo' => ['Maximale bestandsgrootte is 5MB.']]);
        }
    }

    protected function publicUrl(?string $path): ?string
    {
        if (! is_string($path) || trim($path) === '') {
            return null;
        }
        if (! Storage::disk('public')->exists($path)) {
            return null;
        }

        return Storage::disk('public')->url($path);
    }
}
