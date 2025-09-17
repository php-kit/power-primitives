<?php

declare(strict_types=1);

final class TestSuite
{
    /** @var array<string, callable> */
    private array $tests = [];

    /** @var array<int, array{name: string, success: bool, error?: Throwable}> */
    private array $results = [];

    public function add(string $name, callable $test): void
    {
        if (isset($this->tests[$name])) {
            throw new RuntimeException("Duplicate test name: {$name}");
        }

        $this->tests[$name] = $test;
    }

    public function run(): bool
    {
        $allPassed = true;

        foreach ($this->tests as $name => $test) {
            try {
                $test();
                $this->results[] = ['name' => $name, 'success' => true];
            } catch (Throwable $e) {
                $allPassed = false;
                $this->results[] = ['name' => $name, 'success' => false, 'error' => $e];
            }
        }

        $this->printSummary();

        return $allPassed;
    }

    private function printSummary(): void
    {
        $total = count($this->results);
        $failures = array_filter($this->results, static fn(array $result): bool => !$result['success']);

        foreach ($this->results as $result) {
            if ($result['success']) {
                echo "✔ {$result['name']}\n";
            } else {
                /** @var Throwable $error */
                $error = $result['error'];
                echo "✖ {$result['name']}\n   {$error->getMessage()}\n";
            }
        }

        echo "\n{$total} test" . ($total === 1 ? '' : 's') . " run";
        if ($failures) {
            echo ", " . count($failures) . " failed.\n";
        } else {
            echo ", all passed.\n";
        }
    }
}
