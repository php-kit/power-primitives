<?php

declare(strict_types=1);

final class PowerStringTest extends TestCase
{
    protected function register(): void
    {
        $this->test('PowerString::of and cast', function (): void {
            $s = PowerString::of('hello');
            $this->assertSame('hello', (string) $s);

            $raw = 'world';
            PowerString::cast($raw);
            $this->assertInstanceOf(PowerString::class, $raw);
            $this->assertSame('world', (string) $raw);
        });

        $this->test('PowerString append prepend and concat', function (): void {
            $s = PowerString::of('hello');
            $s->append(' world')->prepend('Say ');
            $s->concat('!', PowerString::of(' :)'));
            $this->assertSame('Say hello world! :)', (string) $s);
        });

        $this->test('PowerString charAt and charCodeAt', function (): void {
            $s = PowerString::of('Ångström');
            $this->assertSame('Å', $s->charAt(0));
            $this->assertSame(mb_ord('Å'), $s->charCodeAt(0));
        });

        $this->test('PowerString count ends includes and index', function (): void {
            $s = PowerString::of('abracadabra');
            $this->assertSame(11, $s->count());
            $this->assertTrue($s->endsWith('bra'));
            $this->assertTrue($s->includes('cad'));
            $this->assertSame(3, $s->indexOf('a', 2));
            $this->assertSame(10, $s->lastIndexOf('a'));
        });

        $this->test('PowerString regex helpers', function (): void {
            $s = PowerString::of('Color: #ff0000');
            $matches = $s->match('/#([0-9a-f]+)/i');
            $this->assertEquals(['#ff0000', 'ff0000'], $matches);

            $index = $s->indexOfPattern('/#[0-9a-f]+/i');
            $this->assertSame(7, $index);

            $matchText = null;
            $found = $s->search('/#[0-9a-f]+/i', 0, $matchText);
            $this->assertSame(7, $found);
            $this->assertSame('#ff0000', $matchText);
        });

        $this->test('PowerString slice substr substring', function (): void {
            $s = PowerString::of('hamburger');
            $s->slice(4, 8);
            $this->assertSame('urge', (string) $s);

            $s->substr(0, 2);
            $this->assertSame('ur', (string) $s);

            $s = PowerString::of('abcdef');
            $s->substring(2, 5);
            $this->assertSame('cde', (string) $s);
        });

        $this->test('PowerString split methods', function (): void {
            $s = PowerString::of('a,b,c');
            $parts = $s->split(',')->all();
            $this->assertSame(['a', 'b', 'c'], $parts);

            $s = PowerString::of('1 2 3');
            $parts = $s->splitByPattern('/\s+/')->all();
            $this->assertSame(['1', '2', '3'], $parts);
        });

        $this->test('PowerString repeat replace and startsWith', function (): void {
            $s = PowerString::of('ha');
            $s->repeat(3);
            $this->assertSame('hahaha', (string) $s);

            $s->replace('/ha/', 'ho');
            $this->assertSame('hohaha', (string) $s);
            $this->assertTrue($s->startsWith('ho'));
        });

        $this->test('PowerString case transforms and trim', function (): void {
            $s = PowerString::of('  Mixed Case  ');
            $s->trim();
            $this->assertSame('Mixed Case', (string) $s);

            $this->assertSame('mixed case', (string) PowerString::of('MIXED CASE')->toLowerCase());
            $this->assertSame('LOUD', (string) PowerString::of('loud')->toUpperCase());
        });

        $this->test('PowerString array access', function (): void {
            $s = PowerString::of('word');
            $this->assertTrue(isset($s[0]));
            $this->assertSame('w', $s[0]);
            $s[0] = 's';
            $this->assertSame('sord', (string) $s);
            unset($s[1]);
            $this->assertSame('srd', (string) $s);
        });
    }
}
