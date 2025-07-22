<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class MakeModule extends Command
{
    /**
     * Nama perintah dan parameternya.
     */
    protected $signature = 'make:module {name : The name of the module (e.g., User)}';

    /**
     * Deskripsi perintah.
     */
    protected $description = 'Generate a module structure under app/, including Controllers, Models, etc.';

    /**
     * Subfolder standar untuk setiap modul.
     */
    protected array $folders = [
        'Controllers',
        'Models',
        'Providers',
        'Repositories',
        'Services',
        'Requests',
    ];

    /**
     * Lokasi stub default.
     */
    protected string $stubDir = 'Stubs';

    /**
     * Menjalankan perintah utama.
     */
    public function handle(): void
    {
        $module = Str::studly($this->argument('name'));
        $basePath = app_path($module);

        if (File::exists($basePath)) {
            $this->warn("Module '{$module}' already exists.");
            return;
        }

        $this->createFolders($basePath);
        $this->generateProvider($module);
        $this->info("✅ Module '{$module}' created successfully.");
    }

    /**
     * Membuat semua subfolder yang dibutuhkan.
     */
    protected function createFolders(string $basePath): void
    {
        File::makeDirectory($basePath, 0755, true);
        foreach ($this->folders as $folder) {
            $path = "{$basePath}/{$folder}";
            File::makeDirectory($path, 0755, true);
            $this->line("📁 Created: {$path}");
        }
    }

    /**
     * Generate file provider dari stub.
     */
    protected function generateProvider(string $module): void
    {
        $stubPath = base_path("{$this->stubDir}/Modules/Provider.stub");
        $targetPath = app_path("{$module}/Providers/{$module}ModuleServiceProvider.php");

        if (!File::exists($stubPath)) {
            $this->error("❌ Stub file not found: {$stubPath}");
            return;
        }

        $content = str_replace('{{ module }}', $module, File::get($stubPath));
        File::put($targetPath, $content);

        $this->line("📄 Created: {$targetPath}");
    }
}
