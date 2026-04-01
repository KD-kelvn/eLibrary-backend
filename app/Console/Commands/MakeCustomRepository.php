<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeCustomRepository extends Command
{
    protected $signature = 'make:custom-repository {name} {--module=} {--force} {--model=} {--demo}';

    protected $description = 'Create a new repository with optional module support and demo methods';

    public function handle(): void
    {
        $name = $this->argument('name');
        $module = $this->option('module');
        $force = $this->option('force');
        $model = $this->option('model');
        $demo = $this->option('demo');

        // Ensure the name ends with "Repository" for consistency
        if (! Str::endsWith($name, 'Repository')) {
            $name = $name.'Repository';
        }

        // Handle nested repositories
        $repositoryName = Str::studly(class_basename($name));
        $namespacePath = Str::studly(dirname($name));

        if ($module) {
            $modulePath = base_path("domains/{$module}");
            if (! File::exists($modulePath)) {
                $this->error("Module {$module} does not exist.");

                return;
            }

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

        if (File::exists($filePath) && ! $force) {
            $this->error('Repository already exists!');

            return;
        }

        File::put($filePath, $this->getRepositoryStub($repositoryName, $namespace, $module, $model, $demo));

        $this->info('Repository created successfully!');
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
