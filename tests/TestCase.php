<?php

declare(strict_types=1);

abstract class TestCase
{
    protected TestSuite $suite;

    public function __construct(TestSuite $suite)
    {
        $this->suite = $suite;
        $this->register();
    }

    abstract protected function register(): void;

    protected function test(string $name, callable $fn): void
    {
        $this->suite->add($name, function () use ($fn): void {
            $fn();
        });
    }

    protected function assertTrue(bool $condition, string $message = ''): void
    {
        if (!$condition) {
            throw new RuntimeException($message ?: 'Failed asserting that condition is true.');
        }
    }

    protected function assertFalse(bool $condition, string $message = ''): void
    {
        if ($condition) {
            throw new RuntimeException($message ?: 'Failed asserting that condition is false.');
        }
    }

    protected function assertSame($expected, $actual, string $message = ''): void
    {
        if ($expected !== $actual) {
            $this->fail($expected, $actual, $message ?: 'Failed asserting that two values are the same.');
        }
    }

    protected function assertEquals($expected, $actual, string $message = ''): void
    {
        if ($expected != $actual) {
            $this->fail($expected, $actual, $message ?: 'Failed asserting that two values are equal.');
        }
    }

    protected function assertNull($actual, string $message = ''): void
    {
        if ($actual !== null) {
            throw new RuntimeException($message ?: 'Failed asserting that value is null.');
        }
    }

    protected function assertCount(int $expected, iterable $value, string $message = ''): void
    {
        if (count($value) !== $expected) {
            throw new RuntimeException($message ?: "Failed asserting count of {$expected}.");
        }
    }

    protected function assertInstanceOf(string $className, $value, string $message = ''): void
    {
        if (!($value instanceof $className)) {
            throw new RuntimeException($message ?: "Failed asserting that value is instance of {$className}.");
        }
    }

    private function fail($expected, $actual, string $message): void
    {
        $expectedString = var_export($expected, true);
        $actualString = var_export($actual, true);
        throw new RuntimeException("{$message}\nExpected: {$expectedString}\nActual: {$actualString}");
    }
}
