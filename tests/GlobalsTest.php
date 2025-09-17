<?php

declare(strict_types=1);

final class GlobalsTest extends TestCase
{
    protected function register(): void
    {
        $this->test('PA helper', function (): void {
            $pa = PA([1, 2, 3]);
            $this->assertInstanceOf(PowerArray::class, $pa);
            $this->assertSame([1, 2, 3], $pa->all());
        });

        $this->test('asPA returns singleton wrapper', function (): void {
            $data = [1, 2, 3];
            $pa1 = asPA($data);
            $pa2 = asPA($data);
            $this->assertSame($pa1, $pa2);
            $pa1->append(4);
            $this->assertSame([1, 2, 3, 4], $data);
        });

        $this->test('toPA converts variable', function (): void {
            $data = [1, 2];
            $converted = toPA($data);
            $this->assertInstanceOf(PowerArray::class, $converted);
            $this->assertInstanceOf(PowerArray::class, $data);
        });

        $this->test('PS helper', function (): void {
            $ps = PS('hi');
            $this->assertInstanceOf(PowerString::class, $ps);
            $this->assertSame('hi', (string) $ps);
        });

        $this->test('asPS reuses singleton', function (): void {
            $text = 'foo';
            $ps1 = asPS($text);
            $ps2 = asPS($text);
            $this->assertSame($ps1, $ps2);
            $ps1->append('bar');
            $this->assertSame('foobar', $text);
        });

        $this->test('toPS converts string to wrapper', function (): void {
            $text = 'wrap me';
            $converted = toPS($text);
            $this->assertInstanceOf(PowerString::class, $converted);
            $this->assertInstanceOf(PowerString::class, $text);
        });
    }
}
