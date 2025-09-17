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

        $digits = max(2, strlen((string) $total));

        foreach ($this->results as $index => $result) {
            $number = str_pad((string) ($index + 1), $digits, '0', STR_PAD_LEFT);

            if ($result['success']) {
                echo "{$number}. ✔ {$result['name']}\n";
            } else {
                /** @var Throwable $error */
                $error = $result['error'];
                echo "{$number}. ✖ {$result['name']}\n   {$error->getMessage()}\n";
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
