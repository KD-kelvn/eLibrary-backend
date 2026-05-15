<?php

namespace App\Console\Concerns;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

trait GeneratesScaffoldFiles
{
    use InteractsWithCommandPrompts;

    protected function shouldWriteFile(bool $force, string $filePath): bool
    {
        if (! File::exists($filePath)) {
            return true;
        }

        if ($force) {
            return true;
        }

        return $this->promptOverwrite($force, $filePath);
    }

    protected function writeScaffoldFile(string $directory, string $filename, string $content, bool $force): bool
    {
        if (! File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $filePath = $directory.'/'.$filename;

        if (! $this->shouldWriteFile($force, $filePath)) {
            if ($this instanceof Command) {
                $this->warn("Skipped existing file: {$filePath}");
            }

            return false;
        }

        File::put($filePath, $content);

        if ($this instanceof Command) {
            $this->line("Created: {$filePath}");
        }

        return true;
    }

    protected function moduleNamespace(string $module, string $segment, ?string $group = null): string
    {
        $namespace = 'Modules\\'.Str::studly($module).'\\'.$segment;

        if (filled($group)) {
            $namespace .= '\\'.Str::studly($group);
        }

        return $namespace;
    }

    protected function appNamespace(string $segment, ?string $group = null): string
    {
        $namespace = 'App\\'.$segment;

        if (filled($group)) {
            $namespace .= '\\'.Str::studly($group);
        }

        return $namespace;
    }

    protected function namespacePathFromName(string $name): string
    {
        $path = Str::studly(dirname($name));

        return $path !== '.' ? str_replace('/', '\\', $path) : '';
    }

    protected function modelStubContent(string $modelName, string $namespace, bool $auditable): string
    {
        $baseClass = $auditable ? 'BaseModelWithAudits' : 'BaseModal';

        return "<?php\n\n".
            "namespace {$namespace};\n\n".
            "use App\Models\\{$baseClass};\n\n".
            "class {$modelName} extends {$baseClass}\n".
            "{\n".
            "    protected \$fillable = [\n".
            "        //\n".
            "    ];\n".
            "}\n";
    }

    protected function resourceStubContent(string $modelName, string $namespace): string
    {
        $resourceName = Str::studly($modelName).'Resource';

        return "<?php\n\n".
            "namespace {$namespace};\n\n".
            "use Illuminate\Http\Request;\n".
            "use Illuminate\Http\Resources\Json\JsonResource;\n\n".
            "class {$resourceName} extends JsonResource\n".
            "{\n".
            "    /**\n".
            "     * @return array<string, mixed>\n".
            "     */\n".
            "    public function toArray(Request \$request): array\n".
            "    {\n".
            "        return [\n".
            "            //\n".
            "        ];\n".
            "    }\n".
            "}\n";
    }

    protected function seederStubContent(string $modelName, string $namespace): string
    {
        $seederName = Str::studly($modelName).'Seeder';

        return "<?php\n\n".
            "namespace {$namespace};\n\n".
            "use Illuminate\Database\Seeder;\n\n".
            "class {$seederName} extends Seeder\n".
            "{\n".
            "    public function run(): void\n".
            "    {\n".
            "        //\n".
            "    }\n".
            "}\n";
    }

    protected function migrationStubContent(string $name, bool $auditable): string
    {
        $table = Str::plural(Str::snake($name));
        $auditableColumns = $auditable
            ? "\n            \$table->auditableWithDeletes();"
            : '';

        return "<?php\n\n".
            "use Illuminate\Database\Migrations\Migration;\n".
            "use Illuminate\Database\Schema\Blueprint;\n".
            "use Illuminate\Support\Facades\Schema;\n\n".
            "return new class extends Migration\n".
            "{\n".
            "    public function up(): void\n".
            "    {\n".
            "        Schema::create('{$table}', function (Blueprint \$table) {\n".
            "            \$table->id();{$auditableColumns}\n".
            "            \$table->timestamps();\n".
            "            \$table->softDeletes();\n".
            "        });\n".
            "    }\n\n".
            "    public function down(): void\n".
            "    {\n".
            "        Schema::dropIfExists('{$table}');\n".
            "    }\n".
            "};\n";
    }

    protected function controllerStubContent(string $name, string $namespace): string
    {
        $controllerName = Str::studly($name).'Controller';

        return "<?php\n\n".
            "namespace {$namespace};\n\n".
            "use App\Http\Controllers\Controller;\n".
            "use Illuminate\Http\Request;\n\n".
            "class {$controllerName} extends Controller\n".
            "{\n".
            "    public function index(): void\n".
            "    {\n".
            "        //\n".
            "    }\n\n".
            "    public function store(Request \$request): void\n".
            "    {\n".
            "        //\n".
            "    }\n\n".
            "    public function show(string \$id): void\n".
            "    {\n".
            "        //\n".
            "    }\n\n".
            "    public function update(Request \$request, string \$id): void\n".
            "    {\n".
            "        //\n".
            "    }\n\n".
            "    public function destroy(string \$id): void\n".
            "    {\n".
            "        //\n".
            "    }\n".
            "}\n";
    }

    protected function repositoryStubContent(string $name, string $namespace, ?string $module = null): string
    {
        $repositoryName = Str::studly($name).'Repository';
        $modelName = Str::studly($name);
        $modelNamespace = $module
            ? 'Modules\\'.Str::studly($module).'\\Models\\'.$modelName
            : 'App\\Models\\'.$modelName;

        return "<?php\n\n".
            "namespace {$namespace};\n\n".
            "use {$modelNamespace};\n\n".
            "class {$repositoryName}\n".
            "{\n".
            "    public function __construct(protected {$modelName} \$model) {}\n".
            "}\n";
    }

    protected function traitStubContent(string $name, string $namespace): string
    {
        $traitName = Str::studly($name).'Trait';

        return "<?php\n\n".
            "namespace {$namespace};\n\n".
            "trait {$traitName}\n".
            "{\n".
            "    //\n".
            "}\n";
    }
}
