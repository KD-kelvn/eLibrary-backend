<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeModelWithExtras extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    // Update signature to include group option
    protected $signature = 'make:model-with-extras {name} {--module=} {--auditable=} {--group=}';

    // In the handle method, add after existing variable declarations
    public function handle(): void
    {
        $name = $this->argument('name');
        $module = $this->option('module');
        $auditable = $this->option('auditable');
        $group = $this->option('group');

        // If group is not provided, ask for confirmation
        if (! $group) {
            if ($this->confirm('Would you like to organize these files in a group folder?', false)) {
                $group = $this->ask('Please provide the group folder name:');
            }
        }

        // Ensure the name does not end with "Controller"
        if (Str::endsWith($name, 'Model')) {
            $this->error('The model name should not end with "Model". Please try again.');

            return;
        }

        $basePath = app_path();

        // Update the paths to include group if provided
        $modelName = Str::studly($name);
        // $observerName = $modelName . 'Observer';
        $resourceName = $modelName.'Resource';
        // $policyName = $modelName . 'Policy';
        $seederName = $modelName.'Seeder';
        $migrationDateTime = now()->format('Y_m_d_His');
        $migrationFileName = $migrationDateTime.'_create_'.Str::plural(Str::snake($name)).'_table.php';

        if ($module) {
            $modulePath = __DIR__.'/../../../domains/'.$module;
            if (! File::exists($modulePath)) {
                $this->error('The module does not exist. Please try again.'.$modulePath);

                return;
            }

            $groupPath = $group ? '/'.Str::studly($group) : '';

            $this->createFile($modulePath.'/src/Models'.$groupPath, $modelName.'.php', $this->getModelStub($name, $module, $auditable, $group));
            // $this->createFile($modulePath . '/src/Observers' . $groupPath, $observerName . '.php', $this->getObserverStub($name, $module, $group));
            $this->createFile($modulePath.'/src/Http/Resources'.$groupPath, $resourceName.'.php', $this->getResourceStub($name, $module, $group));
            // $this->createFile($modulePath . '/src/Policies' . $groupPath, $policyName . '.php', $this->getPolicyStub($name, $module, $group));
            $this->createFile($modulePath.'/database/seeders'.$groupPath, $seederName.'.php', $this->getSeederStub($name, $module, $group));
            $this->createFile($modulePath.'/database/migrations'.$groupPath, $migrationFileName, $this->getMigrationStub($name, $group));
        } else {
            $groupPath = $group ? '/'.Str::studly($group) : '';

            $this->createFile($basePath.'/Models'.$groupPath, $modelName.'.php', $this->getModelStub($name, '', $auditable, $group));
            // $this->createFile($basePath . '/Observers' . $groupPath, $observerName . '.php', $this->getObserverStub($name, "", $group));
            $this->createFile($basePath.'/Http/Resources'.$groupPath, $resourceName.'.php', $this->getResourceStub($name, '', $group));
            // $this->createFile($basePath . '/Policies' . $groupPath, $policyName . '.php', $this->getPolicyStub($name, "", $group));
            $this->createFile('./database/seeders'.$groupPath, $seederName.'.php', $this->getSeederStub($name, '', $group));
            $this->createFile('./database/migrations'.$groupPath, $migrationFileName, $this->getMigrationStub($name, $group));
        }

        $this->info('The command was successful!');
        $this->info('Love your codes,
      love your fellow developers, and
      love your future self by organizing your codebase properly.');
    }

    private function createFile($path, $filename, $content): void
    {
        if (! File::exists($path)) {
            File::makeDirectory($path, 0755, true);
        }
        $filePath = $path.'/'.$filename;
        if (! File::exists($filePath)) {
            File::put($filePath, $content);

        }
    }

    private function getModelStub($name, $module = '', $auditable = 'NO', $group = ''): string
    {
        $namespace = $module ? 'Modules\\'.Str::studly($module).'\\Models'.($group ? '\\'.Str::studly($group) : '') : 'App\Models'.($group ? '\\'.Str::studly($group) : '');
        $modelName = Str::studly($name);
        $fillableName = '$'.'fillable';

        if (strtoupper($auditable) === 'YES') {
            return "<?php \n
        namespace $namespace;\n
        use Illuminate\Database\Eloquent\Model;\n
        use App\Models\BaseModelWithAudits;\n
    
        class $modelName extends BaseModelWithAudits\n
        {
            protected $fillableName = [\n
    
            ];\n
    
    
        }
            ";

        } else {
            return "<?php \n
        namespace $namespace;\n
        use Illuminate\Database\Eloquent\Model;\n
        use App\Models\BaseModal;\n
    
        class $modelName extends BaseModal\n
        {
            protected $fillableName = [\n
    
            ];\n
    
    
        }
            ";
        }
    }

    private function getObserverStub($name, $module = ''): string
    {
        $namespace = $module ? 'Modules\\'.Str::studly($module).'\\Observers' : 'App\Observers';
        $modelImport = $module ? 'Modules\\'.Str::studly($module).'\\Models\\'.$name : 'App\Models\\'.$name;
        $capitalizedModel = Str::studly($name);
        $modelNameLowerCase = '$'.Str::camel($name);

        return "<?php\n\n
namespace $namespace;\n

use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use $modelImport;\n

class ".$capitalizedModel."Observer implements ShouldHandleEventsAfterCommit\n
{
    public function created($capitalizedModel $modelNameLowerCase): void
    {
        // More logics such as notifying admin,
        // Setting a default value,
        // creating default relationship and many others
    }

    public function updated($capitalizedModel $modelNameLowerCase): void
    {
        // More logics such as notifying admin,
        // Setting a default value,
        // creating default relationship and many others
    }

    public function deleted($capitalizedModel $modelNameLowerCase): void
    {
        // More logics such as notifying admin,
        // Setting a default value,
        // creating default relationship and many others
    }

}

";
    }

    private function getResourceStub($name, $module = '', $group = ''): string
    {
        $namespace = $module ? 'Modules\\'.Str::studly($module).'\\Http\Resources'.($group ? '\\'.Str::studly($group) : '') : 'App\Http\Resources'.($group ? '\\'.Str::studly($group) : '');
        $capitalizedName = Str::studly($name);
        $requestVariable = '$'.'request';

        return "<?php \n\n
namespace $namespace;\n

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;\n

class ".$capitalizedName."Resource extends JsonResource\n
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $requestVariable): array
    {
        return [
            // Add your fields here
        ];
    }
}

";
    }

    private function getPolicyStub($name, $module = ''): string
    {
        $namespace = $module ? 'Modules\\'.Str::studly($module).'\\Policies' : 'App\Policies';
        $capitalizedName = Str::studly($name);
        $policyName = $capitalizedName.'Policy';
        $userImport = "Modules\Authentication\Models\User;";
        $modelImport = $module ? 'Modules\\'.Str::studly($module).'\\Models\\'.$capitalizedName : 'App\Models\\'.$capitalizedName;
        $userVariable = '$'.'user';
        $modelVariable = '$'.Str::camel($name);

        return "<?php \n\n
namespace $namespace;\n\n

use $userImport\n
use $modelImport;\n

class $policyName \n
{
    public function view(User $userVariable, $capitalizedName $modelVariable ): bool
    {
        return true;
    }

    public function create(User $userVariable): bool
    {
        return true;
    }

    public function update(User $userVariable, $capitalizedName $modelVariable ): bool
    {
        return true;
    }

    public function delete(User $userVariable, $capitalizedName $modelVariable ): bool
    {
        return true;
    }
     public function forceDelete(User $userVariable, $capitalizedName $modelVariable ): bool
    {
        return false;
    }

}
";
    }

    private function getSeederStub($name, $module = '', $group = ''): string
    {
        $namespace = $module ? 'Modules\\'.Str::studly($module).'\\Database\Seeders'.($group ? '\\'.Str::studly($group) : '') : 'Database\Seeders'.($group ? '\\'.Str::studly($group) : '');
        $capitalizedName = Str::studly($name);

        return "<?php \n\n
namespace $namespace;\n\n

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;\n

class ".$capitalizedName."Seeder extends Seeder\n
{
    public function run(): void
    {
        // Add your seed data here
    }

 }
    ";
    }

    private function getMigrationStub($name): string
    {
        return "<?php\n\n
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;\n

return new class extends Migration\n
{
    public function up(): void
    {
        Schema::create('".Str::plural(Str::snake($name))."', function (Blueprint $".'table) {
            $'.'table->id();
            $'.'table->auditableWithDeletes();
            $'.'table->timestamps();
            $'."table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('".Str::plural(Str::snake($name))."');
    }
};


";
    }
}
