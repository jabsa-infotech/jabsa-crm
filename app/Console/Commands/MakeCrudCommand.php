<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeCrudCommand extends Command
{
    protected $signature = 'make:crud {modelName}';

    protected $description = 'Generate CRUD files for a given model';

    public function handle()
    {
        $modelName = $this->argument('modelName');
        $fields = $this->ask("Enter fields for the database migration (comma-separated): Example: title:string, slug:string, description:text, price:integer");

        $controllerDir = $this->ask('Enter Controller directory: e.g. Admin');
        $viewsDir = $this->ask('Enter views directory: e.g. admin.products');
        $requestPath = $this->ask('Enter request path: e.g. Admin\Product');

        // Generate model
        $this->generateModel($modelName);

        // Generate migration if fields are specified
        if ($fields) {
            $this->generateMigration($modelName, $fields);
        }

        // Generate controller
        $this->generateController($modelName, $controllerDir, $requestPath, $viewsDir);

        // Generate request files
        $this->generateRequests($modelName, $requestPath);

        // Generate views
        $this->generateViews($modelName, $viewsDir);

        $this->info('CRUD files generated successfully!');
    }


    // Request code
    private function generateRequests($modelName, $requestPath)
    {
        $requestNamespace = "App\Http\Requests";

        // Generate StoreRequest
        $storeRequestStub = $this->getStubContent('StoreRequest');
        $storeRequestContent = str_replace(
            [
                '{{ class }}',
                '{{ namespace }}',
                '{{ rules }}',
                '{{ messages }}'
            ],
            [
                "StoreRequest",
                $requestNamespace . '\\' . $requestPath,
                '',
                ''
            ],
            $storeRequestStub
        );

        // Create the directory for the request file if it doesn't exist
        $requestPathParts = explode('\\', $requestPath);
        $storeRequestDir = app_path("Http/Requests/" . implode('/', $requestPathParts));
        if (!File::exists($storeRequestDir)) {
            File::makeDirectory($storeRequestDir, 0755, true, true);
        }

        $storeRequestPath = $storeRequestDir . "/StoreRequest.php";
        file_put_contents($storeRequestPath, $storeRequestContent);

        $this->info("{$modelName} StoreRequest created successfully.");

        // Generate UpdateRequest
        $updateRequestStub = $this->getStubContent('UpdateRequest');
        $updateRequestContent = str_replace(
            [
                '{{ class }}',
                '{{ namespace }}',
                '{{ rules }}',
                '{{ messages }}'
            ],
            [
                "UpdateRequest",
                $requestNamespace . '\\' . $requestPath,
                '',
                ''
            ],
            $updateRequestStub
        );

        $updateRequestPath = $storeRequestDir . "/UpdateRequest.php";
        file_put_contents($updateRequestPath, $updateRequestContent);

        $this->info("{$modelName} UpdateRequest created successfully.");
    }
    // Request code ends


    // Model code
    private function generateModel($modelName)
    {
        $modelPath = app_path("Models/{$modelName}.php");

        if (file_exists($modelPath)) {
            $confirmOverwrite = $this->confirm("Model {$modelName} already exists. Do you want to overwrite it?", false);
            if (!$confirmOverwrite) {
                $this->info("Model {$modelName} generation aborted.");
                return;
            }
        }

        $modelNamespace = "App\Models";
        $stub = $this->getStubContent('Model');

        $placeholders = [
            '{{ class }}', '{{ namespace }}', '{{ relationships }}'
        ];
        $placeholderValues = [
            $modelName, $modelNamespace, $this->getRelationshipsCode()
        ];

        $content = str_replace($placeholders, $placeholderValues, $stub);
        file_put_contents($modelPath, $content);

        $this->info("Model {$modelName} created successfully.");
    }

    private function getStubContent($stubName)
    {
        $stubPath = resource_path("stubs/{$stubName}.stub");

        if (file_exists($stubPath)) {
            return file_get_contents($stubPath);
        }

        return '';
    }

    private function getRelationshipsCode()
    {
        $relationships = [];
        $addRelationship = $this->confirm("Do you want to add a relationship?", true);

        while ($addRelationship) {
            $relationshipType = $this->choice("Select relationship type:", ['hasOne', 'hasMany', 'belongsTo', 'belongsToMany']);
            $relatedModel = $this->ask("Enter related model name:");
            $foreignKey = $this->ask("Enter foreign key (optional):");
            $localKey = $this->ask("Enter local key (optional):");

            $relationshipCode = $this->generateRelationshipCode($relationshipType, $relatedModel, $foreignKey, $localKey);
            $relationships[] = $relationshipCode;

            $addAnotherRelationship = $this->confirm("Do you want to add another relationship?", true);
            if (!$addAnotherRelationship) {
                break;
            }
        }

        return implode("\n\n    ", $relationships);
    }

    private function generateRelationshipCode($relationshipType, $relatedModel, $foreignKey = null, $localKey = null)
    {
        $relationshipMethodName = strtolower($relatedModel);
        $relationshipCode = '';

        switch ($relationshipType) {
            case 'hasOne':
                $relationshipMethodName = Str::singular(Str::lower(Str::snake($relatedModel)));

                $relationshipCode = "public function {$relationshipMethodName}()\n    {\n        return \$this->hasOne({$relatedModel}::class";
                if ($foreignKey) {
                    $relationshipCode .= ", '{$foreignKey}'";
                }
                if ($localKey) {
                    $relationshipCode .= ", '{$localKey}'";
                }
                $relationshipCode .= ");\n    }";
                break;
            case 'hasMany':
                $relationshipMethodName = Str::plural(Str::lower(Str::snake($relatedModel)));

                $relationshipCode = "public function {$relationshipMethodName}()\n    {\n        return \$this->hasMany({$relatedModel}::class";
                if ($foreignKey) {
                    $relationshipCode .= ", '{$foreignKey}'";
                }
                if ($localKey) {
                    $relationshipCode .= ", '{$localKey}'";
                }
                $relationshipCode .= ");\n    }";
                break;
            case 'belongsTo':
                $relationshipMethodName = Str::singular(Str::lower(Str::snake($relatedModel)));

                $relationshipCode = "public function {$relationshipMethodName}()\n    {\n        return \$this->belongsTo({$relatedModel}::class";
                if ($foreignKey) {
                    $relationshipCode .= ", '{$foreignKey}'";
                }
                if ($localKey) {
                    $relationshipCode .= ", '{$localKey}'";
                }
                $relationshipCode .= ");\n    }";
                break;
            case 'belongsToMany':
                $relationshipMethodName = Str::plural(Str::lower(Str::snake($relatedModel)));

                $relationshipCode = "public function {$relationshipMethodName}()\n    {\n        return \$this->belongsToMany({$relatedModel}::class";
                if ($foreignKey) {
                    $relationshipCode .= ", '{$foreignKey}'";
                }
                if ($localKey) {
                    $relationshipCode .= ", '{$localKey}'";
                }
                $relationshipCode .= ");\n    }";
                break;
        }

        return $relationshipCode;
    }
    // Model code ends


    // Migration code starts
    private function generateMigration($modelName, $fields)
    {
        $migrationName = 'create_' . Str::snake(Str::plural($modelName)) . '_table';
        $tableName = Str::plural(Str::snake($modelName));

        // Generate migration file
        $migrationFileName = date('Y_m_d_His') . '_' . $migrationName;
        $migrationFilePath = database_path('migrations/' . $migrationFileName . '.php');

        $stub = $this->getStubContent('Migration');

        $placeholders = ['{{ table }}', '{{ fields }}'];
        $placeholderValues = [$tableName, $this->getMigrationFields($fields)];

        $content = str_replace($placeholders, $placeholderValues, $stub);
        file_put_contents($migrationFilePath, $content);

        $this->info("Migration {$migrationFileName} created successfully.");
    }

    private function getMigrationFields($fields)
    {
        // Parse the fields string into an array
        $fieldDefinitions = explode(',', $fields);

        $fieldDefinitions = array_map('trim', $fieldDefinitions);
        $fieldDefinitions = array_filter($fieldDefinitions);

        // Generate the migration fields code
        $fieldsCode = '';
        foreach ($fieldDefinitions as $field) {
            $fieldName = trim(Str::before($field, ':'));

            $columnDefinition = explode(':', $field);
            $fieldType = isset($columnDefinition[1]) ? trim($columnDefinition[1]) : 'string';

            $fieldsCode .= "\$table->{$fieldType}('{$fieldName}');\n" . '            ';
        }

        return $fieldsCode;
    }
    // Migration code ends

    // controller code starts
    private function generateController($modelName, $controllerDir, $requestPath, $viewsDirectory = '')
    {
        $controllerNamespace = "App\Http\Controllers";
        $controllerPath = app_path("Http/Controllers/{$controllerDir}");
        if (!File::exists($controllerPath)) {
            File::makeDirectory($controllerPath, 0755, true);
        }

        $stub = $this->getStubContent('Controller');

        $placeholders = [
            '{{ class }}',
            '{{ namespace }}',
            '{{ rootNamespace }}',
            '{{ modelName }}',
            '{{ variable_in_plural }}',
            '{{ variable_in_singular }}',
            '{{ request_path }}',
            '{{ viewDirectory }}'
        ];
        $placeholderValues = [
            $modelName . 'Controller',
            $controllerNamespace . '\\' . $controllerDir,
            'App\\',
            $modelName,
            Str::plural(Str::lower(Str::snake($modelName))),
            Str::singular(Str::lower(Str::snake($modelName))),
            $this->getRequestPath($requestPath),
            $this->getViewsDirectory($viewsDirectory),
        ];

        $content = str_replace($placeholders, $placeholderValues, $stub);

        $controllerPath = app_path("Http/Controllers/{$controllerDir}/{$modelName}Controller.php");

        file_put_contents($controllerPath, $content);

        $this->info("Controller {$modelName}Controller created successfully.");
    }

    private function getRequestPath($requestPath)
    {
        $path = 'App\\Http\\Requests';
        if ($requestPath) {
            $path = $path . "\\" . $requestPath;
        }
        return $path;
    }

    private function getViewsDirectory($viewsDirectory)
    {
        $views = '';
        if ($viewsDirectory) {
            $views = $viewsDirectory . ".";
        }
        return $views;
    }
    // controller code ends

    // views code starts
    private function generateViews($modelName, $viewsDir)
    {
        $viewsBasePath = resource_path('views');
        $viewsDirPath = $viewsBasePath . '/' . $viewsDir;
        $modelViewsDirPath = $viewsBasePath;

        // Create directories if they don't exist
        $viewsDirParts = explode('.', $viewsDir);
        foreach ($viewsDirParts as $dirPart) {
            $modelViewsDirPath .= '/' . $dirPart;

            if (!File::exists($modelViewsDirPath)) {
                File::makeDirectory($modelViewsDirPath, 0755, true, true);
            }
        }


        // Get stub content for each view type
        $indexStub = $this->getViewsStubContent('views/Index');
        $createStub = $this->getViewsStubContent('views/Create');
        $editStub = $this->getViewsStubContent('views/Edit');
        $showStub = $this->getViewsStubContent('views/Show');

        // Generate file paths for each view
        $indexPath = $modelViewsDirPath . '/index.blade.php';
        $createPath = $modelViewsDirPath . '/create.blade.php';
        $editPath = $modelViewsDirPath . '/edit.blade.php';
        $showPath = $modelViewsDirPath . '/show.blade.php';

        // Check if view files already exist
        $overwrite = false;
        if (File::exists($indexPath) || File::exists($createPath) || File::exists($editPath) || File::exists($showPath)) {
            $overwrite = $this->confirm('View files already exist. Do you want to overwrite them?', false);
        }

        // Write view files
        if ($overwrite || !File::exists($indexPath)) {
            file_put_contents($indexPath, $indexStub);
            $this->info("index.blade.php created successfully.");
        }

        if ($overwrite || !File::exists($createPath)) {
            file_put_contents($createPath, $createStub);
            $this->info("create.blade.php created successfully.");
        }

        if ($overwrite || !File::exists($editPath)) {
            file_put_contents($editPath, $editStub);
            $this->info("edit.blade.php created successfully.");
        }

        if ($overwrite || !File::exists($showPath)) {
            file_put_contents($showPath, $showStub);
            $this->info("show.blade.php created successfully.");
        }
    }
    private function getViewsStubContent($stubName)
    {
        $stubPath = resource_path("stubs/{$stubName}.stub");

        if (file_exists($stubPath)) {
            return file_get_contents($stubPath);
        }

        return '';
    }
    // views code ends
}
