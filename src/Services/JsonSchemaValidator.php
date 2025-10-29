<?php

namespace Carone\Content\Services;

use JsonSchema\Validator;
use JsonSchema\Constraints\Constraint;
use JsonSchema\SchemaStorage;
use JsonSchema\Uri\UriRetriever;
use Carone\Content\Exceptions\ValidationException;
use Illuminate\Support\Facades\Config;

class JsonSchemaValidator
{
    private string $schemasPath;

    public function __construct()
    {
        $this->schemasPath = Config::get('content.validation.schemas_path', __DIR__ . '/../../schemas');
    }

    /**
     * Validate page content against the page schema
     */
    public function validatePage(array $data): void
    {
        $this->validate($data, 'page.json');
    }

    /**
     * Validate block content against the block schema
     */
    public function validateBlock(array $data): void
    {
        $this->validate($data, 'block.json');
    }

    /**
     * Validate data against a specific schema file
     */
    public function validate(array $data, string $schemaFile): void
    {
        $schemaPath = $this->schemasPath . '/' . $schemaFile;
        
        if (!file_exists($schemaPath)) {
            throw new ValidationException("Schema file not found: {$schemaFile}");
        }

        $schema = json_decode(file_get_contents($schemaPath));
        
        if ($schema === null) {
            throw new ValidationException("Invalid JSON in schema file: {$schemaFile}");
        }

        $validator = new Validator();
        $dataObject = json_decode(json_encode($data));
        
        $validator->validate($dataObject, $schema);

        if (!$validator->isValid()) {
            $errors = [];
            foreach ($validator->getErrors() as $error) {
                $errors[] = sprintf('[%s] %s', $error['property'], $error['message']);
            }
            
            throw new ValidationException('JSON Schema validation failed: ' . implode(', ', $errors));
        }
    }

    /**
     * Check if strict validation is enabled
     */
    public function isStrictValidationEnabled(): bool
    {
        return Config::get('content.validation.strict', true);
    }
}