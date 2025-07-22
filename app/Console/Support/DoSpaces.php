<?php
namespace App\Console\Support;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;

class DoSpaces
{
    public static function upload(
        UploadedFile $file,
        string $directory = '',
        ?string $customFileName = null
    ) {
        try {
            // Tentukan folder berdasarkan environment
            $envFolder = config('app.debug') ? 'deployment' : 'production';
            // Ambil ekstensi file
            $extension = $file->getClientOriginalExtension();
            // Gunakan nama custom jika ada, jika tidak, pakai nama asli yang di-slug + waktu
            $baseName = $customFileName
                ? Str::slug(pathinfo($customFileName, PATHINFO_FILENAME))
                : time() . '_' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
            $fileName = "{$baseName}.{$extension}";
            // Susun path folder
            $directory = trim($directory, '/'); // Bersihkan slash di awal/akhir
            $pathDirectory = "esas-assets/{$envFolder}/{$directory}";
            $filePath = "{$pathDirectory}/{$fileName}";
            // Upload ke storage
            Storage::disk('spaces')->putFileAs($pathDirectory, $file, $fileName, [
                'ACL' => 'public-read',
            ]);
            // Dapatkan URL file
            $fileUrl = Storage::disk('spaces')->url($filePath);
            return [
                'url' => $fileUrl,
                'path' => $filePath,
                'filename' => $fileName,
            ];
        } catch (\Exception $e) {
            Log::error('File upload failed: ' . $e->getMessage());
            return $e->getMessage();
        }
    }

    public static function remove(string $filePath)
    {
        try {
            $storage = Storage::disk('spaces');
            if ($storage->exists($filePath)) {
                $storage->delete($filePath);
                return true;
            } else {
                Log::warning("File not found on Spaces: {$filePath}");
                return false;
            }
        } catch (\Exception $e) {
            Log::error("Failed to delete file from Spaces: " . $e->getMessage());
            return false;
        }
    }
}
