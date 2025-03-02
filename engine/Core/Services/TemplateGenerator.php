<?php

namespace Forge\Core\Services;

/**
 * TemplateGenerator Class
 *
 * This class is responsible for generating files from templates. It reads a template file,
 * replaces placeholders with provided values, and writes the output to a specified path.
 * It also includes a method to ask questions interactively.
 *
 * Example usage:
 * ```php
 * \$templateGenerator = new TemplateGenerator();
 * \$replacements = [
 *     '{{ placeholder1 }}' => 'Value1',
 *     '{{ placeholder2 }}' => 'Value2',
 * ];
 * \$templateGenerator->generateFileFromTemplate('path/to/template.php.template', '/output/path/file.php', \$replacements);
 * ```
 */
class TemplateGenerator
{
    /**
     * The base path for template files.
     *
     * @var string
     */
    protected string $baseTemplatePath;

    /**
     * TemplateGenerator constructor.
     *
     * @param string $baseTemplatePath The base path for template files. Defaults to BASE_PATH . '/engine/Core/Templates/'.
     */
    public function __construct(string $baseTemplatePath = BASE_PATH . '/engine/Core/Templates/')
    {
        $this->baseTemplatePath = $baseTemplatePath;
    }

    /**
     * Generates a file from a template by replacing placeholders with provided values.
     *
     * @param string $templateName The name of the template file relative to the base template path.
     * @param string $outputPath The path where the generated file will be saved.
     * @param array<string, string> $replacements Associative array of placeholders and their replacement values.
     * @throws \Exception If the template file cannot be read.
     */
    public function generateFileFromTemplate(string $templateName, string $outputPath, array $replacements): void
    {
        $templatePath = $this->baseTemplatePath . $templateName;
        $templateContent = file_get_contents($templatePath);

        if ($templateContent === false) {
            throw new \Exception("Error: Could not read template file from: {$templatePath}");
        }

        $fileContent = str_replace(array_keys($replacements), array_values($replacements), $templateContent);
        file_put_contents($outputPath, $fileContent);
    }

    /**
     * Asks a question and returns the user's response or a default value if no response is given.
     *
     * @param string $questionText The question to ask the user.
     * @param string $default The default value to return if the user does not provide a response.
     * @return string The user's response or the default value.
     */
    public function askQuestion(string $questionText, string $default): string
    {
        $answer = readline($questionText . " [{$default}]: ");
        return $answer ?: $default;
    }
}
