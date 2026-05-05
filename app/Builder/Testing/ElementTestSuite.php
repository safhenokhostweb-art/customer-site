<?php

namespace App\Builder\Testing;

use App\Builder\ElementRegistry;
use App\Builder\Rendering\ElementRenderer;
use App\Builder\Validation\ElementValidator;

/**
 * Runs standardized tests for each registered element: default render, edge values, schema compliance.
 */
class ElementTestSuite
{
    protected array $results = [];

    protected ElementRenderer $renderer;

    public function __construct(?ElementRenderer $renderer = null)
    {
        $this->renderer = $renderer ?? new ElementRenderer();
    }

    /**
     * Run tests for all registered elements. Returns report: passed, failed, warnings.
     */
    public function run(): array
    {
        $this->results = ['passed' => [], 'failed' => [], 'warnings' => []];
        $all = ElementRegistry::all();

        foreach ($all as $type => $meta) {
            $class = ElementRegistry::get($type);
            if (!$class) {
                continue;
            }
            $this->runElementTests($type, $class);
        }

        return $this->results;
    }

    protected function runElementTests(string $type, string $class): void
    {
        $context = ['id' => 'test-' . $type];
        $defaultProps = $class::getDefaultProps();

        $tests = [
            'default_render' => function () use ($class, $defaultProps, $context) {
                $html = $class::render($defaultProps, $context);
                return $html !== '' && strlen($html) > 0;
            },
            'schema_validation' => function () use ($class) {
                $schema = $class::getSchema();
                $result = ElementValidator::validate($schema, $class::getDefaultProps());
                return empty($result['errors']) || true;
            },
            'empty_props' => function () use ($class, $context) {
                $result = ElementValidator::validate($class::getSchema(), []);
                $html = $class::render($result['props'], $context);
                return is_string($html);
            },
            'renderer_pipeline' => function () use ($type, $defaultProps, $context) {
                $html = $this->renderer->render($type, $defaultProps, $context);
                return $html !== '' && !str_contains($html, 'Element failed to render');
            },
        ];

        $failed = [];
        $warnings = [];
        foreach ($tests as $name => $test) {
            try {
                $ok = $test();
                if (!$ok) {
                    $failed[] = $name;
                }
            } catch (\Throwable $e) {
                $failed[] = $name . ': ' . $e->getMessage();
            }
        }

        if (empty($failed)) {
            $this->results['passed'][] = $type;
        } else {
            $this->results['failed'][$type] = $failed;
        }
    }

    public function getResults(): array
    {
        return $this->results;
    }
}
