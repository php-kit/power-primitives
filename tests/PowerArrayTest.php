<?php

declare(strict_types=1);

final class PowerArrayTest extends TestCase
{
    protected function register(): void
    {
        $this->test('PowerArray::of copies data', function (): void {
            $data = ['a' => 1, 'b' => 2];
            $array = PowerArray::of($data);
            $this->assertSame($data, $array->all());
            $data['c'] = 3;
            $this->assertCount(2, $array->all());
        });

        $this->test('PowerArray::append and prepend', function (): void {
            $array = PowerArray::of([1, 2]);
            $array->append(3, 4)->prepend(0);
            $this->assertSame([0, 1, 2, 3, 4], $array->all());
        });

        $this->test('PowerArray::binarySearch', function (): void {
            $array = PowerArray::of([1, 3, 5, 7]);
            $probe = -1;
            $found = $array->binarySearch(5, $probe, fn(int $left, int $right): int => $left <=> $right);
            $this->assertTrue($found);
            $this->assertSame(2, $probe);
            $found = $array->binarySearch(6, $probe, fn(int $left, int $right): int => $left <=> $right);
            $this->assertFalse($found);
            $this->assertSame(3, $probe);
        });

        $this->test('PowerArray::extract and fields', function (): void {
            $array = PowerArray::of([
                ['id' => 1, 'name' => 'Alice', 'age' => 30],
                ['id' => 2, 'name' => 'Bob'],
            ]);

            $array->extract(['id', 'age']);
            $this->assertSame([[1, 30], [2, null]], $array->all());

            $array = PowerArray::of(['id' => 1, 'name' => 'Alice', 'age' => 30]);
            $array->fields(['name', 'missing'], 'n/a');
            $this->assertSame(['name' => 'Alice', 'missing' => 'n/a'], $array->all());
        });

        $this->test('PowerArray::filter find and findAll', function (): void {
            $array = PowerArray::of([
                ['id' => 1, 'name' => 'Alice'],
                ['id' => 2, 'name' => 'Bob'],
                ['id' => 3, 'name' => 'Alice'],
            ]);

            $match = $array->find('name', 'Alice');
            $this->assertEquals(['id' => 1, 'name' => 'Alice'], $match);

            $array->findAll('name', 'Alice');
            $this->assertCount(2, $array->all());
            $keys = array_keys($array->all());
            $this->assertSame([0, 2], $keys);
        });

        $this->test('PowerArray::getColumn and getColumns', function (): void {
            $array = PowerArray::of([
                ['id' => 1, 'name' => 'Alice'],
                ['id' => 2, 'name' => 'Bob'],
            ]);
            $array->getColumn('name');
            $this->assertSame(['Alice', 'Bob'], $array->all());

            $array = PowerArray::of([
                ['id' => 1, 'name' => 'Alice', 'team' => 'A'],
                ['id' => 2, 'name' => 'Bob', 'team' => 'B'],
            ]);
            $array->getColumns(['id', 'team']);
            $this->assertSame([
                ['id' => 1, 'team' => 'A'],
                ['id' => 2, 'team' => 'B'],
            ], $array->all());
        });

        $this->test('PowerArray::group', function (): void {
            $array = PowerArray::of([
                ['type' => 'animal', 'color' => 'red'],
                ['type' => 'robot', 'color' => 'red'],
                ['type' => 'animal', 'color' => 'blue'],
            ]);

            $array->group('type', 'color');
            $this->assertTrue(isset($array->A['animal']['red']));
            $this->assertCount(1, $array->A['robot']['red']);
        });

        $this->test('PowerArray::hidrate and toClass', function (): void {
            $array = PowerArray::of([
                ['id' => 1, 'name' => 'Alice'],
            ]);
            $array->hidrate(DummyModel::class);
            $first = $array->first();
            $this->assertInstanceOf(DummyModel::class, $first);
            $this->assertSame('Alice', $first->name);

            $map = PowerArray::of(['id' => 10, 'name' => 'Jane']);
            $object = $map->toClass(DummyModel::class);
            $this->assertInstanceOf(DummyModel::class, $object);
            $this->assertSame(10, $object->id);
        });

        $this->test('PowerArray::indexBy and merge', function (): void {
            $array = PowerArray::of([
                ['id' => 1, 'name' => 'Alice'],
                ['id' => 2, 'name' => 'Bob'],
            ]);
            $array->indexBy('id');
            $this->assertTrue(isset($array->A[1]));

            $array->merge(['extra' => true]);
            $this->assertTrue($array->A['extra']);
        });

        $this->test('PowerArray::map and mapColumns', function (): void {
            $array = PowerArray::of([
                ['id' => 1, 'qty' => 2, 'price' => 5.0],
                ['id' => 2, 'qty' => 1, 'price' => 10.0],
            ]);

            $array->map(fn(array $row): float => $row['qty'] * $row['price']);
            $this->assertSame([10.0, 10.0], $array->all());

            $array = PowerArray::of([
                ['id' => 1, 'qty' => 2, 'price' => 5.0],
            ]);
            $array->mapColumns(['qty', 'price'], fn(int $qty, float $price): array => ['total' => $qty * $price]);
            $this->assertSame([['total' => 10.0]], $array->all());
        });

        $this->test('PowerArray::order and slicing', function (): void {
            $array = PowerArray::of([
                ['name' => 'Bob', 'score' => 5],
                ['name' => 'Alice', 'score' => 10],
                ['name' => 'Carol', 'score' => 7],
            ]);

            $array->orderBy('score', SORT_DESC);
            $this->assertSame('Alice', $array->first()['name']);

            $sum = $array->reduce(fn(int $carry, array $item): int => $carry + $item['score'], 0);
            $this->assertSame(22, $sum);

            $array->slice(0, 2)->reindex();
            $this->assertCount(2, $array->all());
        });

        $this->test('PowerArray::splice strip and prune', function (): void {
            $array = PowerArray::of([0, null, '', 3]);
            $array->prune()->prune_empty();
            $this->assertSame([0, 3], array_values($array->all()));

            $array = PowerArray::of([1, 2, 3, 4]);
            $array->splice(1, 2, ['x']);
            $this->assertSame([1, 'x', 4], array_values($array->all()));

            $array->stripFirst();
            $array->stripLast();
            $this->assertSame(['x'], array_values($array->all()));
        });

        $this->test('PowerArray serialization', function (): void {
            $array = PowerArray::of(['a' => 1]);
            $payload = $array->serialize();
            $restored = PowerArray::of([]);
            $restored->unserialize($payload);
            $this->assertSame(['a' => 1], $restored->all());
        });

        $this->test('PowerArray::joinRecords and join', function (): void {
            $left = PowerArray::of([
                ['id' => 1, 'name' => 'Alice'],
                ['id' => 2, 'name' => 'Bob'],
            ]);
            $right = PowerArray::of([
                ['id' => 2, 'email' => 'bob@example.com'],
            ]);

            $left->joinRecords($right, 'id');
            $this->assertEquals(['id' => 2, 'name' => 'Bob', 'email' => 'bob@example.com'], $left->A[1]);

            $string = $left->join(',');
            $this->assertInstanceOf(PowerString::class, $string);
            $this->assertTrue(str_contains((string) $string, 'bob@example.com'));
        });

        $this->test('PowerArray implements ArrayAccess', function (): void {
            $array = PowerArray::of(['first' => 1]);
            $array['second'] = 2;
            $this->assertSame(2, $array['second']);
            unset($array['first']);
            $this->assertFalse(isset($array['first']));
        });
    }
}

final class DummyModel
{
    public int $id;
    public string $name;
}
