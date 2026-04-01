<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeCustomModel extends Command
{
    protected $signature = 'make:custom-model {name} {--auditable} {--force} {--module=}';

    protected $description = 'Create a new model with optional auditable functionality and module support';

    public function handle(): void
    {
        $name = $this->argument('name');
        $auditable = $this->option('auditable');
        $force = $this->option('force');
        $module = $this->option('module');

        // If auditable is not provided, ask interactively
        if (! $auditable) {
            $auditable = $this->confirm('Would you like to make this model auditable?', false);
        }

        // Ensure the name does not end with "Model"
        if (Str::endsWith($name, 'Model')) {
            $this->error('The model name should not end with "Model". Please try again.');

            return;
        }

        // Handle nested models (e.g., Admin/User)
        $modelName = Str::studly(class_basename($name));
        $namespacePath = Str::studly(dirname($name));

        if ($module) {
            // Handle module path
            $modulePath = base_path("domains/{$module}");
            if (! File::exists($modulePath)) {
                $this->error("Module {$module} does not exist.");

                return;
            }

            $namespace = 'Modules\\'.Str::studly($module).'\\Models'.
                ($namespacePath !== '.' ? '\\'.str_replace('/', '\\', $namespacePath) : '');
            $path = $modulePath.'/src/Models/'.str_replace('\\', '/', $namespacePath);
        } else {
            // Handle regular app path
            $namespace = 'App\Models'.($namespacePath !== '.' ? '\\'.str_replace('/', '\\', $namespacePath) : '');
            $path = app_path('Models/'.str_replace('\\', '/', $namespacePath));
        }

        // Create directory if it doesn't exist
        if (! File::exists($path)) {
            File::makeDirectory($path, 0755, true);
        }

        $filePath = $path.'/'.$modelName.'.php';

        // Check if file exists and force option is not set
        if (File::exists($filePath) && ! $force) {
            $this->error('Model already exists!');

            return;
        }

        // Generate model content
        $content = $this->getModelStub($modelName, $namespace, $auditable);

        // Create the file
        File::put($filePath, $content);

        $this->info('Model created successfully!');
        $this->info('Love your codes, love your fellow developers, and love your future self by organizing your codebase properly.');
    }

    private function getModelStub(string $modelName, string $namespace, bool $auditable): string
    {
        $baseClass = $auditable ? 'BaseModelWithAudits' : 'BaseModal';
        $fillableName = '$fillable';

        return "<?php\n\n".
            "namespace {$namespace};\n\n".
            "use App\Models\\{$baseClass};\n\n".
            "class {$modelName} extends {$baseClass}\n".
            "{\n".
            "    protected {$fillableName} = [\n".
            "        //\n".
            "    ];\n".
            "}\n";
    }
}
