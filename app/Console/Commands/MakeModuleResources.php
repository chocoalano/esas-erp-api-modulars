<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class MakeModuleResources extends Command
{
    protected $signature = 'make:module-resources
        {name : Resource name (e.g., User)}
        {--module= : Module name (e.g., Admin)}
        {--table= : Table name (e.g., users)}';

    protected $description = 'Generate Controller, Request, Repository, and Service for a module';

    protected string $stubDir = 'Stubs/Resources';

    public function handle(): void
    {
        $resource = Str::studly($this->argument('name'));
        $module = Str::studly($this->option('module'));

        if (!$module) {
            $this->error('❌ Please provide --module=name');
            return;
        }

        $basePath = app_path($module);
        if (!File::exists($basePath)) {
            $this->error("❌ Module '{$module}' does not exist.");
            return;
        }

        $table = $this->option('table') ?? Str::snake(Str::plural($resource));
        if (!Schema::hasTable($table)) {
            $this->error("❌ Table '{$table}' does not exist.");
            return;
        }

        $permissions = $this->generatePermissions($table);
        $this->assignPermissionsToRoles($permissions);

        $columns = Schema::getColumnListing($table);
        $rules = $this->generateValidationRules($columns, $table);

        $this->generateFile('Controller', $module, $resource, 'Controllers', "{$resource}Controller.php");
        $this->generateFile('Request', $module, $resource, "Requests/{$resource}", "{$resource}Request.php", [
            '{{ rules }}' => $rules
        ]);
        $this->generateFile('IndexRequest', $module, $resource, "Requests/{$resource}", "{$resource}IndexRequest.php");
        $this->generateFile('FileRequest', $module, $resource, "Requests/{$resource}", "{$resource}FileRequest.php");
        $this->generateFile('RepositoryInterface', $module, $resource, 'Repositories/Contracts', "{$resource}RepositoryInterface.php");
        $this->generateFile('Repository', $module, $resource, 'Repositories', "{$resource}Repository.php");
        $this->generateFile('Service', $module, $resource, 'Services', "{$resource}Service.php");

        $pdfHeaders = $this->generatePdfHeaders($table);
        $pdfRows = $this->generatePdfRows($table);

        $this->generateFile('Pdf', $module, $resource, "Views/pdf/{$resource}", "pdf.blade.php", [
            '{{ reportTitle }}' => "{$resource} Report",
            '{{ tableHeaders }}' => $pdfHeaders,
            '{{ tableRows }}' => $pdfRows,
        ]);

        $this->appendRoute($module, $resource);

        $this->info("✅ Completed: {$resource} resources generated in module {$module}.\n");
        $this->info("💡 Don't forget to bind {$resource}RepositoryInterface to {$resource}Repository in your {$module} service provider.\nExample:\n\n\$this->app->bind(\n    {$resource}RepositoryInterface::class,\n    {$resource}Repository::class\n);");
    }

    protected function generatePdfHeaders(string $table): string
    {
        $columns = Schema::getColumnListing($table);
        $ignore = ['id', 'created_at', 'updated_at', 'deleted_at'];

        $headers = [];

        foreach ($columns as $column) {
            if (in_array($column, $ignore)) continue;
            $headers[] = "<th>" . Str::headline(str_replace('_', ' ', $column)) . "</th>";
        }

        $headers[] = "<th>Created At</th>";
        $headers[] = "<th>Updated At</th>";

        return implode("\n", $headers);
    }

    protected function generatePdfRows(string $table): string
    {
        $columns = Schema::getColumnListing($table);
        $ignore = ['id', 'created_at', 'updated_at', 'deleted_at'];

        $rows = [];

        foreach ($columns as $column) {
            if (in_array($column, $ignore)) continue;
            $rows[] = "<td>{{ \$a->{$column} ?? '-' }}</td>";
        }

        $rows[] = "<td>{{ \$a->created_at ?? '-' }}</td>";
        $rows[] = "<td>{{ \$a->updated_at ?? '-' }}</td>";

        return implode("\n", $rows);
    }

    protected function generatePermissions(string $table): array
    {
        $permissions = [
            "view_{$table}",
            "view_any_{$table}",
            "create_{$table}",
            "update_{$table}",
            "delete_{$table}",
            "delete_any_{$table}",
            "forcedelete_{$table}",
            "forcedelete_any_{$table}",
            "restore_{$table}",
            "export_{$table}",
            "import_{$table}",
        ];

        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web'
            ]);
        }

        return $permissions;
    }

    protected function assignPermissionsToRoles(array $permissions): void
    {
        $roles = Role::all();

        foreach ($roles as $role) {
            $role->givePermissionTo($permissions);
        }
    }

    protected function generateFile(string $type, string $module, string $resource, string $subPath, string $filename, array $extraReplace = []): void
    {
        $stubFile = match ($type) {
            'Controller' => 'Controller.stub',
            'Request' => 'Request.stub',
            'IndexRequest' => 'IndexRequest.stub',
            'FileRequest' => 'FileRequest.stub',
            'Repository' => 'Repository.stub',
            'RepositoryInterface' => 'RepositoryInterface.stub',
            'Service' => 'Service.stub',
            'Pdf' => 'Pdf.stub',
            default => throw new \InvalidArgumentException("Unknown type: {$type}"),
        };

        $stubPath = base_path("{$this->stubDir}/{$stubFile}");
        $targetPath = app_path("{$module}/{$subPath}/{$filename}");

        if (!File::exists($stubPath)) {
            $this->error("❌ Missing stub: {$stubPath}");
            return;
        }

        $content = File::get($stubPath);
        $replace = array_merge([
            '{{ module }}' => $module,
            '{{ class }}' => $resource,
            '{{ model }}' => Str::camel($resource),
        ], $extraReplace);

        $content = str_replace(array_keys($replace), array_values($replace), $content);

        File::ensureDirectoryExists(dirname($targetPath));
        File::put($targetPath, $content);

        $this->line("📄 Created: {$targetPath}");
    }

    protected function appendRoute(string $module, string $resource): void
    {
        $routePath = app_path("{$module}/routes.php");

        if (!File::exists($routePath)) {
            File::put($routePath, "<?php\n\nuse Illuminate\\Support\\Facades\\Route;\n\n");
        }

        $routeName = Str::kebab(Str::plural($resource));
        $controllerClass = "App\\{$module}\\Controllers\\{$resource}Controller";

        $routeEntry = <<<ROUTE

Route::prefix('{$routeName}')->controller({$controllerClass}::class)->group(function () {
    Route::get('/', 'index');
    Route::get('create', 'create');
    Route::post('/', 'store');
    Route::get('deleted', 'deleted');
    Route::get('export', 'xlsx');
    Route::get('print', 'print');
    Route::post('import', 'import');
    Route::get('{id}', 'show');
    Route::get('{id}/edit', 'edit');
    Route::put('{id}', 'update');
    Route::delete('{id}', 'destroy');
    Route::post('{id}/restore', 'restore');
    Route::delete('{id}/force', 'forceDelete');
});
ROUTE;

        $currentContent = File::get($routePath);
        if (!Str::contains($currentContent, "{$resource}Controller")) {
            File::append($routePath, $routeEntry);
            $this->line("🛠️ Route added to: {$routePath}");
        } else {
            $this->warn("⚠️ Route already exists for {$resource} in {$routePath}");
        }
    }

    protected function generateValidationRules(array $columns, string $table): string
    {
        $rules = [];

        foreach ($columns as $column) {
            if (in_array($column, ['id', 'created_at', 'updated_at', 'deleted_at'])) continue;

            $rule = match (true) {
                Str::contains($column, 'email') => "'{$column}' => 'required|email|unique:{$table},{$column}'",
                Str::contains($column, ['image', 'avatar', 'photo']) => "'{$column}' => 'nullable|image|mimes:jpeg,png,jpg,svg,webp|max:5048'",
                Str::contains($column, 'password') => "'{$column}' => 'required|string|min:8|confirmed'",
                Str::contains($column, 'slug') => "'{$column}' => 'required|string|unique:{$table},{$column}'",
                Str::contains($column, 'icon') => "'{$column}' => 'nullable|string|max:255'",
                Str::contains($column, 'phone') => "'{$column}' => 'required|string|regex:/^(\+62|62|0)8[1-9][0-9]{6,9}$/'",
                Str::contains($column, 'date') => "'{$column}' => 'required|date'",
                Str::contains($column, ['time', 'at']) => "'{$column}' => 'required|date_format:H:i'",
                Str::contains($column, 'price') => "'{$column}' => 'required|numeric|min:0'",
                Str::contains($column, 'amount') => "'{$column}' => 'required|integer|min:0'",
                Str::contains($column, 'qty') => "'{$column}' => 'required|integer|min:1'",
                Str::contains($column, 'status') => "'{$column}' => 'required|in:active,inactive,pending,approved,rejected'",
                Str::contains($column, 'desc') => "'{$column}' => 'nullable|string|max:1000'",
                Str::contains($column, 'title') => "'{$column}' => 'required|string|max:255'",
                Str::contains($column, 'name') => "'{$column}' => 'required|string|max:255'",
                Str::contains($column, 'url') => "'{$column}' => 'required|url'",
                Str::contains($column, 'address') => "'{$column}' => 'nullable|string|max:500'",
                Str::contains($column, 'file') => "'{$column}' => 'nullable|file|max:10240'",
                Str::contains($column, 'gender') => "'{$column}' => 'required|in:male,female,other'",
                Str::contains($column, 'is_') => "'{$column}' => 'required|boolean'",
                Str::contains($column, 'code') => "'{$column}' => 'required|string|max:50'",
                Str::endsWith($column, '_id') => "'{$column}' => 'required|integer|exists:related_table,id'",
                default => "'{$column}' => 'required|string|max:255'",
            };

            $rules[] = "        {$rule}";
        }

        return implode(",\n", $rules);
    }
}
