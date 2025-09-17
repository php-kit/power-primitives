# Power Primitives

> PHP arrays and strings with the fluency of modern collection APIs

## Why Power Primitives?

Plain PHP arrays and strings are wonderfully flexible, yet they quickly turn into walls of procedural helper calls.
Loop counters, temporary variables and manual checks for missing keys clutter the happy path of your application.

The `PowerArray` and `PowerString` classes wrap native values in a lightweight object-oriented facade that embraces PHP 8 and makes common transformations readable, chainable and safe.

```php
<?php
use function PA;

$emails = PA($users)
    ->filter(fn ($user) => $user['isActive'])
    ->orderBy('lastLogin', SORT_DESC)
    ->map(fn ($user) => $user['email'])
    ->all();
```

No temporary arrays, no custom utility library scattered throughout your project — just fluent, discoverable methods that compose naturally.

## Installation

```bash
composer require php-kit/power-primitives
```

*Requires PHP 8.1 or newer (tested up to 8.4-dev).* The package ships with a
self-contained test runner, so no external dependencies are required to verify
your installation.

## A tour of `PowerArray`

Create an instance with `PowerArray::of()` (copying data) or `asPA()` for a
zero-cost wrapper around an existing array. From there you gain a toolbox of
methods grouped into categories:

### Construction & inspection
- `of`, `on`, `cast`, and helpers `PA`, `asPA`, `toPA` for flexible wrapping.
- `all`, `first`, `last`, `count`, `getIterator` expose the underlying data.
- Native `ArrayAccess` support lets you interact using `$array[$key]` syntax.

### Transformation & chaining
- `append`, `prepend`, `merge`, `stripFirst`, `stripLast`, `splice`, `slice`,
  `reindex` and `sort` reshape the structure fluently.
- `map`, `mapColumns`, `reduce`, `repeat`, `join`, `toClass`, `hidrate`,
  `toLowerCase` (via `PowerString`) and `joinRecords` help you build pipelines
  without leaving the method chain.

### Filtering & searching
- `filter`, `find`, `findAll`, `missing`, `indexOf`, `binarySearch`, `orderBy`
  cover common lookup patterns with optional strict comparisons.
- `prune` removes `null` values while `prune_empty` removes `null` and empty
  strings—perfect for tidying user input.

### Field level helpers
- `extract`, `fields`, `getColumn`, `getColumns`, `mapColumns`, `iterateColumns`
  target nested keys in arrays or objects without verbose loops.
- `group`, `indexBy`, `joinRecords`, and `hidrate` help you reshape datasets into
  trees, keyed maps or domain objects in a single expression.

### Serialization & compatibility
- `serialize`, `unserialize`, `__serialize`, `__unserialize` ensure forward-
  compatible persistence.
- `join` converts arbitrarily nested values into a `PowerString`, stringifying
  objects and arrays in a readable JSON form.

The entire API is designed for method chaining so you can describe your intent
at a high level and only drop down to native arrays when you call `all()`.

## A tour of `PowerString`

`PowerString` takes the same philosophy to text processing. Wrap your string via
`PowerString::of()` or the `PS()` helper and unlock a Unicode-aware toolkit:

### Creation & mutation
- `append`, `prepend`, `concat`, `repeat`, `replace`, `substr`, `substring`,
  `slice`, `trim`, `trimLeft`, `trimRight`, `toLowerCase`, `toUpperCase` let you
  manipulate strings without juggling offsets.

### Inspection & navigation
- `charAt`, `charCodeAt`, `length`, `count`, `includes`, `startsWith`,
  `endsWith`, `indexOf`, `lastIndexOf`, `indexOfPattern`, `search` provide
  familiar ergonomics with multibyte safety.
- Pattern helpers (`match`, `splitByPattern`) transparently upgrade your regexes
  to Unicode mode and optionally capture global matches.

### Interoperability
- `split` returns a `PowerArray` for downstream transformations.
- Array-style indexing works (`$string[0]`, `isset($string[5])`, `unset($string[1])`).
- `normalize` (if the intl extension is available) unifies Unicode sequences.

## Convenience helpers

Short global functions live in `src/globals.php`:

- `PA($array)` / `asPA(&$array)` / `toPA(&$var)` for array wrappers.
- `PS($string)` / `asPS(&$string)` / `toPS(&$var)` for string wrappers.

They make quick scripting or prototyping a breeze:

```php
<?php
use function PS;

echo PS('  Laravel ')
    ->trim()
    ->toUpperCase(); // outputs LARAVEL
```

## Comparing to procedural PHP

Traditional helpers often look like this:

```php
$emails = [];
foreach ($users as $user) {
    if (!empty($user['active']) && isset($user['email'])) {
        $emails[] = strtolower($user['email']);
    }
}
```

With Power Primitives the same intent reads like a story:

```php
$emails = PA($users)
    ->filter(fn ($user) => !empty($user['active']))
    ->getColumn('email')
    ->map('strtolower')
    ->all();
```

Each method describes *what* you want, not the low-level mechanics of *how* to
obtain it. Tests in this repository guard every method, so you can chain with
confidence.

## Running the tests

The project ships with a deterministic, dependency-free test suite covering the
full API surface.

```bash
php tests/run.php
```

The runner prints a friendly checklist so you immediately know the health of
your installation. (CI environments can simply check for an exit code of `0`.)

## Contributing

Issues and pull requests are welcome! The codebase embraces strict types, PHP 8
features and thoughtful documentation. Please run `php tests/run.php` before
submitting patches.

## License

Power Primitives is open-source software licensed under the
[MIT license](http://opensource.org/licenses/MIT).
