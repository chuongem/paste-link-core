<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

class SwaggerGenerate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'swagger:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Parse openapi.yml and write storage/api-docs/api-docs.json for L5-Swagger';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Hand-written source spec and the JSON file L5-Swagger serves from.
        $yamlPath = resource_path('swagger/openapi.yml');
        $jsonPath = storage_path('api-docs/api-docs.json');

        // Abort early when the source spec is missing.
        if (! File::exists($yamlPath)) {
            $this->error("OpenAPI YAML file not found at: {$yamlPath}");

            return self::FAILURE;
        }

        try {
            // Parse the YAML spec into a PHP array.
            $spec = Yaml::parse(File::get($yamlPath));
        } catch (ParseException $e) {
            // Report the exact syntax error so it can be fixed before serving.
            $this->error('Invalid OpenAPI YAML: '.$e->getMessage());

            return self::FAILURE;
        }

        // Create the output directory if it does not exist yet.
        File::ensureDirectoryExists(storage_path('api-docs'));

        // Encode the spec as JSON into the path L5-Swagger reads.
        File::put($jsonPath, json_encode($spec, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        $this->info("Swagger documentation generated: {$jsonPath}");

        return self::SUCCESS;
    }
}
