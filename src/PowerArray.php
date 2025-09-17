<?php

declare(strict_types=1);


class PowerArray implements ArrayAccess, Countable, IteratorAggregate
{
  /**
   * The array representation of this instance.
   *
   * Treat this as read-only - **do not modify** it directly!
   *
   * @var array<int|string, mixed>
   */
  public array $A = [];

  /**
   * Creates an uninitialized instance of `PowerArray` that should not be used until the `A` property is set.
   *
   * Use {@see PowerArray::of()} or {@see PowerArray::on()} for creating instances.
   */
  protected function __construct () { }

  /**
   * Typecasts the given array variable to a `PowerArray` that wraps that array.
   *
   * <p>**Warning:** the variable passed as argument will be converted to an instance of `PowerArray`.
   *
   * @param array $src A variable of type array.
   * @return static The same value of `$src` after the typecast.
   */
  public static function cast (&$src): static
  {
    if ($src instanceof static) {
      return $src;
    }

    if (!is_array ($src)) {
      throw new InvalidArgumentException ('PowerArray::cast expects an array reference.');
    }

    $x    = new static ();
    $x->A = $src;
    $src  = $x;

    return $x;
  }

  /**
   * Creates an instance of `PowerArray` that handles a copy (on write) of the given array or iterator.
   *
   * @param array|Iterator|IteratorAggregate $src
   * @return static
   */
  public static function of (iterable $src = []): static
  {
    $x = new static ();

    if (is_array ($src)) {
      $x->A = $src;
      return $x;
    }

    if ($src instanceof IteratorAggregate) {
      $src = $src->getIterator ();
    }

    if (!($src instanceof Iterator)) {
      throw new InvalidArgumentException ('PowerArray::of expects an array or iterable source.');
    }

    $x->A = iterator_to_array ($src);

    return $x;
  }

  /**
   * Returns a singleton instance of `PowerArray` that modifies the given array.
   * <p>**Warning:** this method returns **always** the same instance. This is meant to be a wrapper for applying
   * extension methods to an existing array variable. You should **not** store the instance anywhere, as it will lead
   * to unexpected problems. If  you need to do that, use {@see PowerArray::of} instead.
   *
   * @param array $src
   * @return static
   */
  static function on (array & $src)
  {
    static $x;
    if (!isset($x)) $x = new static;
    $x->A =& $src;
    return $x;
  }

  public function __debugInfo ()
  {
    return $this->A;
  }

  /**
   * Reads a value from the given array at the specified index/key.
   * This method implements the [] operator for reading.
   * <br><br>
   * Unlike the usual array access operator [], this method does not generate warnings when
   * the key is not present on the array; instead, it returns null.
   *
   * @param number|string $key The list index or map key.
   *
   * @return mixed
   */
  function __get ($key)
  {
    return isset ($this->A[$key]) ? $this->A[$key] : null;
  }

  /**
   * Implements the [] operator for writing.
   *
   * @param String $key
   * @param mixed  $value
   */
  function __set ($key, $value)
  {
    $this->A[$key] = $value;
  }

  /**
   * Implements the isset operator.
   *
   * @param String $key
   *
   * @return Boolean
   */
  function __isset ($key)
  {
    return isset ($this->A[$key]);
  }

  /**
   * Implements typecasting to string.
   * Outputs a PHP representation of the wrapped array.
   *
   * @return string
   */
  function __toString ()
  {
    return var_export ($this->A, true);
  }

  /**
   * Implements the unset operator.
   *
   * @param String $key
   */
  function __unset ($key)
  {
    unset ($this->A[$key]);
  }

  function all ()
  {
    return $this->A;
  }

  public function append (...$values): self
  {
    foreach ($values as $value) {
      $this->A[] = $value;
    }

    return $this;
  }

  /**
   * Searches for an element on a **sorted** array.
   *
   * @param string   $what       What to search for.
   * @param int      $probe      The position where the element was found, or where it would be if it existed.
   * @param callable $comparator A function that returns zero for equality or a positive or negative number.
   *
   * @return bool True a match was found.
   */
  public function binarySearch (mixed $what, int &$probe, callable $comparator): bool
  {
    $low  = 0;
    $high = count ($this->A) - 1;

    while ($low <= $high) {
      $mid = intdiv ($low + $high, 2);
      $cmp = $comparator ($this->A[$mid], $what);

      if ($cmp === 0) {
        $probe = $mid;
        return true;
      }

      if ($cmp < 0) {
        $low = $mid + 1;
      }
      else {
        $high = $mid - 1;
      }
    }

    $probe = $low;

    return false;
  }

  public function count (): int
  {
    return count ($this->A);
  }

  /**
   * Generates a new array where each element is a list of values extracted from the corresponding element on the input
   * array.
   * Result: array - An array with the same cardinality as the input array.
   *
   * @param array $keys The keys of the values to be extracted from each $array element.
   *
   * @return PowerArray Self, for chaining.
   */
  public function extract (array $keys): self
  {
    $result = [];

    foreach ($this->A as $outerKey => $item) {
      $row = [];

      foreach ($keys as $field) {
        $row[] = self::readField ($item, $field, null);
      }

      $result[$outerKey] = $row;
    }

    $this->A = $result;

    return $this;
  }

  /**
   * Extracts the values with the given keys from a given array, in the same order as the key list.
   *
   * @param array $keys A list of keys to be extracted.
   * @param mixed $def  An optional default value to be returned for non-existing keys.
   * @return PowerArray Self, for chaining.
   * @see PowerArray::extract
   */
  public function fields (array $keys, mixed $def = null): self
  {
    if ($this->A === []) {
      $this->A = [];
      return $this;
    }

    $first = reset ($this->A);

    if (is_array ($first) || is_object ($first)) {
      $result = [];

      foreach ($this->A as $outerKey => $item) {
        $row = [];

        foreach ($keys as $field) {
          $row[$field] = self::readField ($item, $field, $def);
        }

        $result[$outerKey] = $row;
      }

      $this->A = $result;
      return $this;
    }

    $result = [];

    foreach ($keys as $field) {
      $result[$field] = $this->A[$field] ?? $def;
    }

    $this->A = $result;

    return $this;
  }

  /**
   * Calls a filtering function for each element of an array.
   * The function will receive as arguments the array element and its key.
   * It should return a boolean value that indicates whether the element should not be discarded or not.
   *
   * @param callable $fn
   *
   * @return PowerArray Self, for chaining.
   */
  public function filter (callable $fn): self
  {
    $this->A = array_filter ($this->A, $fn, ARRAY_FILTER_USE_BOTH);

    return $this;
  }

  /**
   * Searches an array for the first element where the specified field matches the given value.
   * Supports arrays of objects or arrays of arrays.
   * Result: The value of the first matching element or NULL if none found.
   *
   * @param string $fld
   * @param mixed  $val
   * @param bool   $strict TRUE to perform strict equality testing.
   *
   * @return mixed|null The found element or NULL if none found.
   */
  public function find (string|int $fld, mixed $val, bool $strict = false)
  {
    foreach ($this->A as $item) {
      $candidate = self::readField ($item, $fld, null);

      if ($strict ? $candidate === $val : $candidate == $val) {
        return $item;
      }
    }

    return null;
  }

  /**
   * Extracts from an array all elements where the specified field matches the given value.
   * Supports arrays of objects or arrays of arrays.
   *
   * @param string $fld
   * @param mixed  $val
   * @param bool   $strict TRUE to perform strict equality testing.
   *
   * @return PowerArray Self, for chaining.
   */
  public function findAll (string|int $fld, mixed $val, bool $strict = false): self
  {
    $result = [];

    foreach ($this->A as $key => $item) {
      $candidate = self::readField ($item, $fld, null);

      if ($strict ? $candidate === $val : $candidate == $val) {
        $result[$key] = $item;
      }
    }

    $this->A = $result;

    return $this;
  }

  /**
   * Returns the first element of the array. The array is not modified.
   *
   * @return mixed|null null if the array is empty.
   */
  public function first (): mixed
  {
    return $this->A ? reset ($this->A) : null;
  }

  /**
   * Returns the values from a single column of the array, identified by the column key.
   * This is a simplified implementation of the native array_column function for PHP < 5.5 but it
   * additionally allows fetching properties from an array of objects.
   * Array elements can be objects or arrays.
   * The first element in the array is used to determine the element type for the whole array.
   *
   * @param int|string $key Null value is not supported.
   *
   * @return PowerArray Self, for chaining.
   */
  public function getColumn (string|int $key): self
  {
    $result = [];

    foreach ($this->A as $outerKey => $item) {
      $result[$outerKey] = self::readField ($item, $key, null);
    }

    $this->A = $result;

    return $this;
  }

  /**
   * Returns the values from multiple columns of the array, identified by the given column keys.
   * Array elements can be objects or arrays.
   * The first element in the array is used to determine the element type for the whole array.
   * Result: A list of objects or arrays, each one having the specified keys.
   * If some keys are absent from the input data, they will also be absent from the output.
   *
   * @param array $keys A list of integer or string keys.
   *
   * @return PowerArray Self, for chaining.
   */
  public function getColumns (array $keys): self
  {
    $result = [];

    foreach ($this->A as $outerKey => $item) {
      $row = [];

      foreach ($keys as $key) {
        if (self::hasField ($item, $key)) {
          $row[$key] = self::readField ($item, $key, null);
        }
      }

      $result[$outerKey] = $row;
    }

    $this->A = $result;

    return $this;
  }

  public function getIterator (): Traversable
  {
    return new ArrayIterator ($this->A);
  }

  /**
   * Splits the array by one or more field values, generating a tree-like structure.
   * <p>The first argument is the input array.
   * <p>Each subsequent argument can be a field name or a function that returns the value to split on.
   * <p>Array elements can be arrays or objects.
   *
   * Ex:
   * ```
   * $a->group ($data, 'type', 'date', function (v) { return datePart(v['date']); });
   * ```
   * Ex:
   * ```
   * $a = [
   *   [
   *     "type" => "animal",
   *     "color" => "red",
   *   ],
   *   [
   *     "type" => "animal",
   *     "color" => "green",
   *   ],
   *   [
   *     "type" => "robot",
   *     "color" => "red",
   *   ],
   *   [
   *     "type" => "robot",
   *     "color" => "green",
   *   ],
   *   [
   *     "type" => "robot",
   *     "color" => "blue",
   *   ],
   *   [
   *     "type" => "robot",
   *     "color" => "blue",
   *     "name" => "bee",
   *   ],
   * ];
   * A($a)->group ($data, 'type', 'color');
   * ```
   * Generates:
   * ```
   * [
   *   "animal" => [
   *     "red" => [
   *       [
   *         "type" => "animal",
   *         "color" => "red",
   *       ],
   *     ],
   *     "green" => [
   *       [
   *         "type" => "animal",
   *         "color" => "green",
   *       ],
   *     ],
   *   ],
   *   "robot" => [
   *     "red" => [
   *       [
   *         "type" => "robot",
   *         "color" => "red",
   *       ],
   *     ],
   *     "green" => [
   *       [
   *         "type" => "robot",
   *         "color" => "green",
   *       ],
   *     ],
   *     "blue" => [
   *       [
   *         "type" => "robot",
   *         "color" => "blue",
   *       ],
   *       [
   *         "type" => "robot",
   *         "color" => "blue",
   *         "name" => "bee",
   *       ],
   *     ],
   *   ],
   * ]
   * ```
   *
   * @param string ...$args The field names.
   * @return PowerArray Self, for chaining.
   */
  public function group (...$groupers): self
  {
    $this->A = self::groupByRecursive ($this->A, $groupers);

    return $this;
  }

  /**
   * Converts a PHP array of maps to an array of instances of the specified class.
   *
   * @param string $className
   *
   * @return PowerArray Self, for chaining.
   */
  public function hidrate (string $className): self
  {
    $result = [];

    foreach ($this->A as $key => $item) {
      $result[$key] = self::hydrate ($className, $item);
    }

    $this->A = $result;

    return $this;
  }

  /**
   * Reindexes the array using the specified key field.
   * Array items should be arrays or objects.
   *
   * @param string $field The field name.
   * @return PowerArray Self, for chaining.
   */
  public function indexBy (string|int $field): self
  {
    $result = [];

    foreach ($this->A as $item) {
      $value = self::readField ($item, $field, null);

      if (is_int ($value) || is_string ($value)) {
        $result[$value] = $item;
        continue;
      }

      if ($value === null) {
        continue;
      }

      $result[(string) $value] = $item;
    }

    $this->A = $result;

    return $this;
  }

  /**
   * Gets the key of the first element of the array that matches a given value.
   *
   * @param mixed $value  The value to search for.
   * @param bool  $strict Determines if strict comparison (===) should be used during the search.
   *
   * @return mixed|false The key for needle if it is found in the array, false otherwise.
   *                     If needle is found in haystack more than once, the first matching key is returned. To
   *                     return the keys for all matching values, use array_keys with the optional search_value
   *                     parameter instead.
   */
  public function indexOf (mixed $value, bool $strict = true): int|string|false
  {
    return array_search ($value, $this->A, $strict);
  }

  /**
   * Calls a function for each element of an array.
   * The function will receive one argument for each specified column.
   *
   * @param array    $cols
   * @param callable $fn
   *
   * @return PowerArray Self, for chaining.
   */
  public function iterateColumns (array $cols, callable $fn): self
  {
    foreach ($this->A as $item) {
      $args = [];

      foreach ($cols as $col) {
        $args[] = self::readField ($item, $col, null);
      }

      $fn (...$args);
    }

    return $this;
  }

  /**
   * Join array elements with a string.
   *
   * @param string $glue
   * @return PowerString
   */
  public function join (string $glue = ''): PowerString
  {
    $parts = array_map (
      static function ($value): string {
        if (is_scalar ($value) || $value === null) {
          return (string) $value;
        }

        if (is_object ($value) && method_exists ($value, '__toString')) {
          return (string) $value;
        }

        $encoded = json_encode ($value, JSON_UNESCAPED_UNICODE);

        return $encoded === false ? serialize ($value) : $encoded;
      },
      $this->A
    );

    return PowerString::of (implode ($glue, $parts));
  }

  /**
   * Merges records from two arrays using the specified primary key field.
   * When keys collide, the corresponding values are assumed to be arrays and they are merged.
   *
   * @param array|PowerArray $array
   * @param string           $field
   *
   * @return PowerArray Self, for chaining.
   */
  public function joinRecords (array|self $array, string $field): self
  {
    $other = $array instanceof self ? $array->A : $array;
    $index = [];

    foreach ($other as $record) {
      $key = self::readField ($record, $field, null);

      if ($key === null) {
        continue;
      }

      $index[$key] = $record;
    }

    $result = [];

    foreach ($this->A as $key => $record) {
      $joinKey = self::readField ($record, $field, null);

      if ($joinKey !== null && array_key_exists ($joinKey, $index)) {
        $result[$key] = self::mergeRecord ($record, $index[$joinKey]);
      }
      else {
        $result[$key] = $record;
      }
    }

    $this->A = $result;

    return $this;
  }

  /**
   * Gets all the keys of the array.
   *
   * @return PowerArray Self, for chaining.
   */
  public function keys (): self
  {
    $this->A = array_keys ($this->A);

    return $this;
  }

  /**
   * Gets all the keys of the array that match a given value.
   *
   * @param mixed      $value  Only keys containing these values are returned.
   * @param bool|false $strict Determines if strict comparison (===) should be used during the search.
   * @return PowerArray
   */
  public function keysOf (mixed $value, bool $strict = true): self
  {
    $this->A = array_keys ($this->A, $value, $strict);

    return $this;
  }

  /**
   * Returns the last element of the array. The array is not modified.
   *
   * @return mixed|null null if the array is empty.
   */
  public function last (): mixed
  {
    if (!$this->A) {
      return null;
    }

    $last = end ($this->A);
    reset ($this->A);

    return $last;
  }

  /**
   * Calls a transformation function for each element of the array.
   *
   * The function will receive a value and a key for each array element and it should return a value that will replace
   * the original array element.
   *
   * Unlike array_map, the original keys will be preserved, unless the callback defines the
   * key parameter as a reference and modifies the key.
   *
   * @param callable $fn               The callback.
   * @param bool     $useKeys          [optional] When true, the iteration keys are passed as a second argument to the
   *                                   callback. Set to false for compatibility with native PHP functions used as
   *                                   callbacks, as they will complain if an extra argument is provided.
   * @return PowerArray Self, for chaining.
   */
  public function map (callable $fn, bool $useKeys = true): self
  {
    $result = [];

    foreach ($this->A as $key => $value) {
      $result[$key] = $useKeys ? $fn ($value, $key) : $fn ($value);
    }

    $this->A = $result;

    return $this;
  }

  /**
   * Calls a transformation function for each element of the array.
   * The function will receive one argument for each specified column.
   * It should return an array/object that will replace the original array element.
   * Unlike array_map, the original keys will be preserved.
   *
   * @param array    $cols
   * @param callable $fn
   *
   * @return PowerArray Self, for chaining.
   */
  public function mapColumns (array $cols, callable $fn): self
  {
    $result = [];

    foreach ($this->A as $key => $item) {
      $args = [];

      foreach ($cols as $col) {
        $args[] = self::readField ($item, $col, null);
      }

      $result[$key] = $fn (...$args);
    }

    $this->A = $result;

    return $this;
  }

  /**
   * Merges another array or instance of this class with this one.
   *
   * @param array|PowerArray $v
   */
  public function merge (array|self $v): self
  {
    $values = $v instanceof static ? $v->A : $v;

    foreach ($values as $key => $value) {
      $this->A[$key] = $value;
    }

    return $this;
  }

  /**
   * Checks if either the specified key is missing from the array or it's corresponding value in the array is
   * empty.
   *
   * @param string|int $key An array key / offset.
   *
   * @return bool True if the key is missing or the corresponding value in the array is empty (null or empty string).
   * @see is_empty()
   */
  public function missing (string|int $key): bool
  {
    if (!array_key_exists ($key, $this->A)) {
      return true;
    }

    $value = $this->A[$key];

    return $value === null || $value === '';
  }

  public function offsetExists (mixed $offset): bool
  {
    return array_key_exists ($offset, $this->A);
  }

  public function offsetGet (mixed $offset): mixed
  {
    return $this->A[$offset] ?? null;
  }

  public function offsetSet (mixed $offset, mixed $value): void
  {
    if ($offset === null) {
      $this->A[] = $value;
      return;
    }

    $this->A[$offset] = $value;
  }

  public function offsetUnset (mixed $offset): void
  {
    unset ($this->A[$offset]);
  }

  /**
   * Sorts the array by one or more field values.
   * Ex: orderBy ('volume', SORT_DESC, 'edition', SORT_ASC);
   *
   * @return PowerArray Self, for chaining.
   */
  public function orderBy (...$criteria): self
  {
    if (!$criteria) {
      return $this->sort ();
    }

    $sorts = [];
    $i     = 0;
    $count = count ($criteria);

    while ($i < $count) {
      $field = $criteria[$i++];
      $dir   = SORT_ASC;
      $flags = SORT_REGULAR;

      if ($i < $count && is_int ($criteria[$i]) && ($criteria[$i] === SORT_ASC || $criteria[$i] === SORT_DESC)) {
        $dir = $criteria[$i++];
      }

      if ($i < $count && is_int ($criteria[$i]) && !in_array ($criteria[$i], [SORT_ASC, SORT_DESC], true)) {
        $flags = $criteria[$i++];
      }

      if (!is_string ($field) && !is_int ($field) && !is_callable ($field)) {
        throw new InvalidArgumentException ('orderBy expects field names or callables.');
      }

      $sorts[] = ['field' => $field, 'dir' => $dir, 'flags' => $flags];
    }

    $data = $this->A;

    uasort ($data, function ($a, $b) use ($sorts) {
      foreach ($sorts as $sort) {
        $valueA = is_callable ($sort['field'])
          ? $sort['field'] ($a)
          : self::readField ($a, $sort['field'], null);
        $valueB = is_callable ($sort['field'])
          ? $sort['field'] ($b)
          : self::readField ($b, $sort['field'], null);

        $cmp = self::compareValues ($valueA, $valueB, $sort['flags']);

        if ($cmp !== 0) {
          return $sort['dir'] === SORT_DESC ? -$cmp : $cmp;
        }
      }

      return 0;
    });

    $this->A = $data;

    return $this;
  }

  public function pop (): mixed
  {
    return array_pop ($this->A);
  }

  /**
   * Inserts element at the beginning of the array.
   *
   * @param mixed ...$args One or more elements to prepend to the array.
   * @return PowerArray
   */
  public function prepend (...$values): self
  {
    if ($values) {
      array_unshift ($this->A, ...$values);
    }

    return $this;
  }

  /**
   * Returns the input array stripped of null elements (with strict comparison).
   *
   * @return PowerArray Self, for chaining.
   */
  public function prune (): self
  {
    $this->A = array_filter (
      $this->A,
      static fn ($value) => $value !== null
    );

    return $this;
  }

  /**
   * Returns the input array stripped of empty elements (those that are either `null` or empty strings).
   *
   * @return PowerArray Self, for chaining.
   */
  public function prune_empty (): self
  {
    $this->A = array_filter (
      $this->A,
      static fn ($value) => !($value === null || $value === '')
    );

    return $this;
  }

  /**
   * Iteratively reduce the array to a single value using a callback function.
   *
   * @param callable $fn      The callback function.
   * @param mixed    $initial If the optional initial is available, it will be used at the beginning of the process, or
   *                          as a final result in case the array is empty.
   * @return mixed The resulting value. If the array is empty and initial is not passed, it returns `null`.
   */
  public function reduce (callable $fn, mixed $initial = null): mixed
  {
    return array_reduce ($this->A, $fn, $initial);
  }

  /**
   * Reindexes the current data into a series of sequential integer keys.
   *
   * This is useful to extract the data as a linear array with no discontinuous keys.
   *
   * @return PowerArray Self, for chaining.
   */
  public function reindex (): self
  {
    $this->A = array_values ($this->A);

    return $this;
  }

  public function serialize (): string
  {
    return serialize ($this->__serialize ());
  }

  /**
   * Shifts an element off the beginning of array.
   *
   * @return mixed
   */
  public function shift (): mixed
  {
    return array_shift ($this->A);
  }

  /**
   * Extract a slice of the array.
   *
   * @param int  $start        If offset is non-negative, the sequence will start at that offset in the array. If
   *                           offset is negative, the sequence will start that far from the end of the array.
   * @param int  $len          If length is given and is positive, then the sequence will have that many elements
   *                           in it. If length is given and is negative then the sequence will stop that many
   *                           elements from the end of the array. If it is omitted, then the sequence will have
   *                           everything from offset up until the end of the array.
   * @param bool $preserveKeys Note that `slice()` will reorder and reset the array indices by default. You can
   *                           change this behaviour by setting `$preserveKeys` to true.
   * @return PowerArray Self, for chaining.
   */
  public function slice (int $start, ?int $len = null, bool $preserveKeys = false): self
  {
    $this->A = $len === null
      ? array_slice ($this->A, $start, null, $preserveKeys)
      : array_slice ($this->A, $start, $len, $preserveKeys);

    return $this;
  }

  /**
   * Sorts the array.
   *
   * @param int $flags [optional] See {@see sort()}
   * @return PowerArray Self, for chaining.
   */
  public function sort (?int $flags = null): self
  {
    if ($flags === null) {
      sort ($this->A);
    }
    else {
      sort ($this->A, $flags);
    }

    return $this;
  }

  /**
   * Remove a portion of the array and replace it with something else.
   *
   * @param int        $offset      If offset is positive then the start of removed portion is at that offset from the
   *                                beginning of the input array. If offset is negative then it starts that far from
   *                                the end of the input array.
   * @param int|null   $length      If length is omitted, removes everything from offset to the end of the array. If
   *                                length is specified and is positive, then that many elements will be removed. If
   *                                length is specified and is negative then the end of the removed portion will be
   *                                that many elements from the end of the array. Tip: to remove everything from offset
   *                                to the end of the array when replacement is also specified, use count($input) for
   *                                length.
   * @param array|null $replacement If replacement array is specified, then the removed elements are replaced with
   *                                elements from this array. If offset and length are such that nothing is removed,
   *                                then the elements from the replacement array are inserted in the place specified by
   *                                the offset. Note that keys in replacement array are not preserved. If replacement
   *                                is just one element it is not necessary to put array() around it, unless the
   *                                element is an array itself.
   * @return PowerArray Self, for chaining.
   */
  public function splice (int $offset, ?int $length = null, ?array $replacement = null): self
  {
    if ($replacement === null) {
      $replacement = [];
    }

    if ($length === null) {
      array_splice ($this->A, $offset, count ($this->A), $replacement);
    }
    else {
      array_splice ($this->A, $offset, $length, $replacement);
    }

    return $this;
  }

  /**
   * Discards the first item(s) of the array.
   *
   * @param int $count [optional] How many elements to discard.
   * @return PowerArray
   */
  public function stripFirst (int $count = 1): self
  {
    if ($count <= 0) {
      return $this;
    }

    $this->A = array_slice ($this->A, $count);

    return $this;
  }

  /**
   * Discards the last item(s) of the array.
   *
   * @param int $count [optional] How many elements to discard.
   * @return PowerArray
   */
  public function stripLast (int $count = 1): self
  {
    if ($count <= 0) {
      return $this;
    }

    $this->A = $count >= count ($this->A)
      ? []
      : array_slice ($this->A, 0, -$count);

    return $this;
  }

  /**
   * Converts a PHP array map to an instance of the specified class.
   *
   * @param string $className
   *
   * @return mixed An instance of the specified class.
   */
  public function toClass (string $className): object
  {
    return self::hydrate ($className, $this->A);
  }

  public function __serialize (): array
  {
    return ['A' => $this->A];
  }

  public function __unserialize (array $data): void
  {
    $this->A = isset ($data['A']) && is_array ($data['A']) ? $data['A'] : [];
  }

  public function unserialize (string $serialized): void
  {
    $data = unserialize ($serialized, ['allowed_classes' => true]);

    if (!is_array ($data)) {
      throw new UnexpectedValueException ('Invalid serialization payload for PowerArray.');
    }

    $this->__unserialize ($data);
  }

  /**
   * @param array<int|string, mixed> $data
   * @param array<int, string|callable> $groupers
   * @return array<int|string, mixed>
   */
  private static function groupByRecursive (array $data, array $groupers): array
  {
    if ($groupers === []) {
      return $data;
    }

    $grouper = array_shift ($groupers);
    $groups  = [];

    foreach ($data as $item) {
      $key = is_callable ($grouper)
        ? $grouper ($item)
        : self::readField ($item, $grouper, null);

      if (is_object ($key)) {
        $key = spl_object_hash ($key);
      }
      elseif (is_array ($key)) {
        $key = serialize ($key);
      }

      $groups[$key][] = $item;
    }

    if ($groupers === []) {
      return $groups;
    }

    foreach ($groups as $key => $items) {
      $groups[$key] = self::groupByRecursive ($items, $groupers);
    }

    return $groups;
  }

  private static function hydrate (string $className, mixed $data): object
  {
    if ($data instanceof $className) {
      return clone $data;
    }

    $object = new $className ();

    foreach ((array) $data as $key => $value) {
      $object->{$key} = $value;
    }

    return $object;
  }

  private static function mergeRecord (mixed $left, mixed $right): mixed
  {
    if (is_array ($left)) {
      $result = $left;

      foreach ((array) $right as $key => $value) {
        $result[$key] = $value;
      }

      return $result;
    }

    if (is_object ($left)) {
      $result = clone $left;

      foreach ((array) $right as $key => $value) {
        $result->{$key} = $value;
      }

      return $result;
    }

    return $right;
  }

  private static function hasField (mixed $item, string|int $field): bool
  {
    if (is_array ($item)) {
      return array_key_exists ($field, $item);
    }

    if ($item instanceof ArrayAccess) {
      return $item->offsetExists ($field);
    }

    if (is_object ($item)) {
      return property_exists ($item, (string) $field);
    }

    return false;
  }

  private static function readField (mixed $item, string|int $field, mixed $default): mixed
  {
    if (is_array ($item)) {
      return array_key_exists ($field, $item) ? $item[$field] : $default;
    }

    if ($item instanceof ArrayAccess && $item->offsetExists ($field)) {
      return $item[$field];
    }

    if (is_object ($item) && property_exists ($item, (string) $field)) {
      return $item->{$field};
    }

    return $default;
  }

  private static function compareValues (mixed $a, mixed $b, int $flags): int
  {
    $mode      = $flags & ~SORT_FLAG_CASE;
    $ignoreCase = (bool) ($flags & SORT_FLAG_CASE);

    return match ($mode) {
      SORT_STRING => $ignoreCase ? strcasecmp ((string) $a, (string) $b) : strcmp ((string) $a, (string) $b),
      SORT_NATURAL => $ignoreCase ? strnatcasecmp ((string) $a, (string) $b) : strnatcmp ((string) $a, (string) $b),
      SORT_NUMERIC => ($a <=> $b),
      default => ($a <=> $b),
    };
  }
}
