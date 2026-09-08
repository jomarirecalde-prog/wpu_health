<?php

namespace App\Services;

use App\Models\PortalUser;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class PortalProfilePhotoService
{
    public const MAX_BYTES = 5_242_880; // 5 MB

    /** @var array<string, string> */
    private const ALLOWED_MIMES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    public function __construct(
        private readonly PortalSecurityAuditService $audit,
    ) {}

    /**
     * @return array{path: string, url: string}
     */
    public function store(PortalUser $user, UploadedFile $file): array
    {
        $validated = $this->validateUploadedFile($file);

        $this->ensureStorageDirectory();

        if ($user->profile_photo_path) {
            $this->deleteFile($user->profile_photo_path);
        }

        $filename = Str::random(32).'.'.$validated['extension'];
        $relativePath = 'portal-profiles/'.$user->id.'/'.$filename;
        $absolutePath = Storage::disk('public')->path($relativePath);

        if (! is_dir(dirname($absolutePath))) {
            mkdir(dirname($absolutePath), 0755, true);
        }

        $this->processAndSave($file->getPathname(), $validated['mime'], $absolutePath);

        $user->forceFill(['profile_photo_path' => $relativePath])->save();

        $this->audit->log($user, 'profile_photo_changed', 'Profile picture updated');

        return [
            'path' => $relativePath,
            'url' => Storage::disk('public')->url($relativePath),
        ];
    }

    /**
     * @return array{ok: bool, message: string, mime: string, extension: string}
     */
    public function validateUploadedFile(UploadedFile $file): array
    {
        if (! $file->isValid()) {
            throw new RuntimeException('Upload failed. Please try again.');
        }

        if ($file->getSize() > self::MAX_BYTES) {
            throw new RuntimeException('File too large. Maximum size is 5 MB.');
        }

        $original = $file->getClientOriginalName();
        if (str_contains($original, "\0") || preg_match('/\.(php|phtml|php3|php4|php5|phar|htaccess|cgi|asp|aspx|jsp|exe|sh|bat|cmd)(\.|$)/i', $original)) {
            throw new RuntimeException('File type not allowed.');
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file->getPathname()) ?: 'application/octet-stream';

        if (! isset(self::ALLOWED_MIMES[$mime])) {
            throw new RuntimeException('Invalid file type. Allowed formats: JPG, PNG, WebP.');
        }

        return [
            'ok' => true,
            'message' => '',
            'mime' => $mime,
            'extension' => self::ALLOWED_MIMES[$mime],
        ];
    }

    private function ensureStorageDirectory(): void
    {
        $base = Storage::disk('public')->path('portal-profiles');
        if (! is_dir($base)) {
            mkdir($base, 0755, true);
        }

        $htaccess = $base.DIRECTORY_SEPARATOR.'.htaccess';
        if (! is_file($htaccess)) {
            file_put_contents($htaccess, "<FilesMatch \"\\.(php|phtml|php3|php4|php5|phar|cgi|pl|py)$\">\n    Require all denied\n</FilesMatch>\n");
        }
    }

    private function processAndSave(string $sourcePath, string $mime, string $destPath): void
    {
        if (! extension_loaded('gd')) {
            if (! copy($sourcePath, $destPath)) {
                throw new RuntimeException('Could not save profile photo.');
            }

            return;
        }

        $image = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($sourcePath),
            'image/png' => @imagecreatefrompng($sourcePath),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($sourcePath) : false,
            default => false,
        };

        if ($image === false) {
            throw new RuntimeException('Could not process image file.');
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $size = min($width, $height);
        $srcX = (int) max(0, ($width - $size) / 2);
        $srcY = (int) max(0, ($height - $size) / 2);
        $target = 400;

        $canvas = imagecreatetruecolor($target, $target);
        imagecopyresampled($canvas, $image, 0, 0, $srcX, $srcY, $target, $target, $size, $size);

        if (! imagejpeg($canvas, $destPath, 85)) {
            imagedestroy($image);
            imagedestroy($canvas);
            throw new RuntimeException('Could not save profile photo.');
        }

        imagedestroy($image);
        imagedestroy($canvas);
    }

    private function deleteFile(string $path): void
    {
        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
