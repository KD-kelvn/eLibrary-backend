<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeControllerWithExtras extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    // Update signature to include group option
    protected $signature = 'make:controller-with-extras {name} {--module=} {--group=}';

    // In the handle method, add after existing variable declarations
    public function handle(): void
    {
        $name = $this->argument('name');
        $module = $this->option('module');
        $group = $this->option('group');

        // If group is not provided, ask for confirmation
        if (! $group) {
            if ($this->confirm('Would you like to organize views and requests in a group folder?', false)) {
                $group = $this->ask('Please provide the group folder name:');
            }
        }

        // Ensure the name does not end with "Controller"
        if (Str::endsWith($name, 'Controller')) {
            $this->error('The controller name should not end with "Controller". Please try again.');

            return;
        }

        $basePath = app_path();

        // Define the file paths and names
        $controllerName = Str::studly($name).'Controller';
        // $storeRequest = Str::studly('Store' . $name . 'Request');
        // $updateRequest = Str::studly('Update' . $name . 'Request');
        $repository = Str::studly($name).'Repository';
        $trait = Str::studly($name).'Trait';

        if ($module) {
            $modulePath = __DIR__.'/../../../domains/'.$module;
            if (! File::exists($modulePath)) {
                $this->error('The module does not exist. Please try again.'.$modulePath);

                return;
            }

            $groupPath = $group ? '/'.Str::studly($group) : '';

            $this->createFile($modulePath.'/src/Http/Controllers'.$groupPath, $controllerName.'.php', $this->getControllerStub($name, $module, $group));
            // $this->createFile($modulePath . '/src/Http/Requests' . $groupPath, $storeRequest . '.php', $this->getRequestStub($storeRequest, $module));
            // $this->createFile($modulePath . '/src/Http/Requests' . $groupPath, $updateRequest . '.php', $this->getRequestStub($updateRequest, $module));
            $this->createFile($modulePath.'/src/Repositories'.$groupPath, $repository.'.php', $this->getRepositoryStub($name, $module, $group));
            $this->createFile($modulePath.'/src/Traits'.$groupPath, $trait.'.php', $this->getTraitStub($name, $module, $group));

            // Views with group path
            // $viewPath = $modulePath . '/resources/views' . ($group ? '/' . Str::kebab($group) .$controllerName : '') . '/' . Str::kebab($name);
            // $this->createFile($viewPath, 'create.blade.php', '');
            // $this->createFile($viewPath, 'store.blade.php', '');
            // $this->createFile($viewPath, 'edit.blade.php', '');
            // $this->createFile($viewPath, 'update.blade.php', '');
            // $this->createFile($viewPath, 'destroy.blade.php', '');
        } else {
            $groupPath = $group ? '/'.Str::studly($group) : '';
            // CREATE CONTROLLER
            $this->call('make:controller', ['name' => $groupPath.'/'.$controllerName]);

            // CREATE REQUESTS with group

            // $this->createFile(app_path('Http/Requests' . $groupPath . "/" . Str::studly($name)), $storeRequest . '.php', $this->getRequestStub($storeRequest));
            // $this->createFile(app_path('Http/Requests' . $groupPath . "/". Str::studly($name)), $updateRequest . '.php', $this->getRequestStub($updateRequest));

            // CREATE REPOSITORY AND TRAIT FILES
            $this->createFile(app_path('Repositories'.$groupPath), $repository.'.php', $this->getRepositoryStub($name));
            $this->createFile(app_path('Traits'.$groupPath), $trait.'.php', $this->getTraitStub($name));

            // VIEWS with group path
            // $viewPath = resource_path('views' . ($group ? '/' . Str::kebab($group) : '') . '/' . Str::kebab($name));
            // $this->createFile($viewPath, 'create.blade.php', '');
            // $this->createFile($viewPath, 'store.blade.php', '');
            // $this->createFile($viewPath, 'edit.blade.php', '');
            // $this->createFile($viewPath, 'update.blade.php', '');
            // $this->createFile($viewPath, 'destroy.blade.php', '');
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

    private function getRepositoryStub($name, $module = '', $group = ''): string
    {
        $namespace = $module ? ' Modules\\'.Str::studly($module).'\Repositories'.($group ? '\\'.Str::studly($group).';' : '') : ' App\Repositories'.($group ? '\\'.Str::studly($group).';' : '');

        return "<?php

namespace $namespace 

class ".Str::studly($name)."Repository\n{\n // Repository methods here\n}\n";
    }

    private function getTraitStub($name, $module = '', $group = ''): string
    {
        $namespace = $module ? ' Modules\\'.Str::studly($module).'\Traits'.($group ? '\\'.Str::studly($group).';' : '') : ' App\Traits'.($group ? '\\'.Str::studly($group).';' : '');

        return "<?php

namespace $namespace; 

trait ".Str::studly($name)."Trait\n{\n // Trait methods here\n}\n";
    }

    private function getRequestStub($name, $module = ''): string
    {
        $namespace = $module ? ' Modules\\'.Str::studly($module).'\Http\Requests;' : ' App\Http\Requests';

        return "<?php

namespace $namespace;

use Illuminate\Foundation\Http\FormRequest;


class ".Str::studly($name).' extends FormRequest
{

  public function authorize(): bool
  {
        return false;
   }

  public function rules(): array
   {
        return [];
    }

   public function messages(): array
    {
        return [];
    }
        
}
 ';
    }

    private function getControllerStub($name, $module = '', $group = ''): string
    {
        $namespace = $module ? ' Modules\\'.Str::studly($module).'\Http\Controllers'.($group ? '\\'.Str::studly($group).';' : '') : ' App\Http\Controllers'.($group ? '\\'.Str::studly($group).';' : '');

        return "<?php

namespace $namespace 

use Illuminate\Http\Request;

use App\Http\Controllers\Controller;

class ".Str::studly($name).'Controller extends Controller
{

      public function index()
      {
              // FOR READING RESOURCES
       }

      public function store()
      {
                // FOR CREATING RESOURCES
      }

      public function show($id)
      {
        // FOR READING A SINGLE RESOURCE
      }

    public function update(Request $request, $id)
    {
    // FOR UPDATING RESOURCES
    }

    public function destroy($id)
    {
    // FOR DELETING RESOURCES
    }

        
}
 ';
    }
}
