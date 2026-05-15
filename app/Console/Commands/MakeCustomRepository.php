<?php

namespace App\Console\Commands;

use App\Console\Concerns\GeneratesScaffoldFiles;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

use function Laravel\Prompts\text;

class MakeCustomRepository extends Command
{
    use GeneratesScaffoldFiles;

    protected $signature = 'make:custom-repository {name?} {--module=} {--force} {--model=} {--demo}';

    protected $description = 'Create a new repository with optional module support and demo methods';

    public function handle(): int
    {
        $name = $this->promptName($this->argument('name'), 'Repository name', 'e.g. Book');
        $module = $this->promptModule($this->option('module'));
        $force = $this->option('force');
        $demo = $this->promptDemoMethods($this->option('demo'));

        if (! $this->ensureModuleExists($module)) {
            return self::FAILURE;
        }

        if (! Str::endsWith($name, 'Repository')) {
            $name = $name.'Repository';
        }

        $repositoryName = Str::studly(class_basename($name));
        $namespacePath = Str::studly(dirname($name));
        $model = $this->option('model');

        if (! filled($model)) {
            $model = text(
                label: 'Associated model name',
                placeholder: Str::studly(Str::beforeLast($repositoryName, 'Repository')),
                default: Str::studly(Str::beforeLast($repositoryName, 'Repository')),
                required: true,
            );
        }

        if ($module) {
            $modulePath = base_path("domains/{$module}");
            $namespace = 'Modules\\'.Str::studly($module).'\\Repositories'.
                ($namespacePath !== '.' ? '\\'.str_replace('/', '\\', $namespacePath) : '');
            $path = $modulePath.'/src/Repositories/'.str_replace('\\', '/', $namespacePath);
        } else {
            $namespace = 'App\Repositories'.($namespacePath !== '.' ? '\\'.str_replace('/', '\\', $namespacePath) : '');
            $path = app_path('Repositories/'.str_replace('\\', '/', $namespacePath));
        }

        if (! File::exists($path)) {
            File::makeDirectory($path, 0755, true);
        }

        $filePath = $path.'/'.$repositoryName.'.php';
        $existedBefore = File::exists($filePath);

        if (! $this->writeScaffoldFile($path, "{$repositoryName}.php", $this->getRepositoryStub($repositoryName, $namespace, $module, $model, $demo), $force)) {
            return $existedBefore ? self::INVALID : self::FAILURE;
        }

        $this->info('Repository created successfully!');

        return self::SUCCESS;
    }

    private function getRepositoryStub(string $repositoryName, string $namespace, ?string $module, ?string $model, bool $demo): string
    {
        $modelName = $model ?? Str::studly(Str::beforeLast($repositoryName, 'Repository'));
        $modelNamespace = $module ?
            'Modules\\'.Str::studly($module).'\\Models\\'.$modelName :
            'App\\Models\\'.$modelName;

        $content = "<?php\n\nnamespace {$namespace};\n\n";
        $content .= "use {$modelNamespace};\n";

        if ($demo) {
            $content .= "use Illuminate\Database\Eloquent\Collection;\n";
            $content .= "use Illuminate\Pagination\LengthAwarePaginator;\n";
        }

        $content .= "\nclass {$repositoryName}\n{\n";
        $content .= "    protected \$model;\n\n";
        $content .= "    public function __construct({$modelName} \$model)\n";
        $content .= "    {\n";
        $content .= "        \$this->model = \$model;\n";
        $content .= "    }\n\n";

        if ($demo) {
            $content .= $this->getDemoMethods();
        }

        $content .= "}\n";

        return $content;
    }

    private function getDemoMethods(): string
    {
        return <<<'EOT'
    /**
     * Get all records with optional relations.
     */
    public function all(array $relations = []): Collection
    {
        return $this->model->with($relations)->get();
    }

    /**
     * Get paginated records.
     */
    public function paginate(int $perPage = 15, array $relations = []): LengthAwarePaginator
    {
        return $this->model->with($relations)->paginate($perPage);
    }

    /**
     * Find a record by its ID.
     */
    public function findById(int $id, array $relations = []): ?object
    {
        return $this->model->with($relations)->find($id);
    }

    /**
     * Create a new record.
     */
    public function create(array $data): object
    {
        return $this->model->create($data);
    }

    /**
     * Update an existing record.
     */
    public function update(int $id, array $data): bool
    {
        return $this->model->findOrFail($id)->update($data);
    }

    /**
     * Delete a record.
     */
    public function delete(int $id): bool
    {
        return $this->model->findOrFail($id)->delete();
    }

    /**
     * Get records by a specific field value.
     */
    public function getBy(string $field, mixed $value, array $relations = []): Collection
    {
        return $this->model->where($field, $value)->with($relations)->get();
    }

EOT;
    }
}
