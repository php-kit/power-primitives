<?php

declare(strict_types=1);


/**
 * An object oriented string API for PHP.
 *
 * <p>To retrieve a PHP native string from a PowerString instance or expression, read the `S` property.
 * <p>Nevertheless, you can use power strings directly in many situations where a native string is expected;
 * for example, you can pass them as arguments to native functions that expect strings, and you can use the `==`
 * operator to compare a power string to a native string or to another power string.
 *
 * >##### Notes
 *
 * > Do not use the `===` operator to compare power strings to strings or to other power strings. Use the `==` operator
 * > instead.
 *
 * ><p>Treat `S` as a read only property. Modifying it directly is discouraged.<br>
 * > The reason it is not a method is simply to make expressions with power strings shorter and a little more readable.
 *
 * ><p>All methods support Unicode.
 *
 * ><p>A subset of the API purposefully mimics the Javascript ES6 String object.
 */
class PowerString implements Countable, IteratorAggregate, ArrayAccess
{
  /**
   * The string representation of this instance.
   *
   * Treat this as read-only - **do not modify** it directly!
   *
   * @var string
   */
  public string $S = '';

  /**
   * Creates a new instance of `PowerString`.
   *
   * This is for internal use.
   * Use {@see PowerString::of()} or {@see PowerString::on()} for creating instances.
   */
  protected function __construct () { }

  /**
   * Typecasts the given string variable to a `PowerString` that wraps that string.
   *
   * <p>**Warning:** the variable passed as argument will be converted to an instance of `PowerString`.
   *
   * @param string $src A variable of type `string`.
   * @return PowerString The same value of `$src` after the typecast.
   */
  public static function cast (&$src): static
  {
    if ($src instanceof static) {
      return $src;
    }

    if (!is_string ($src)) {
      throw new InvalidArgumentException ('PowerString::cast expects a string reference.');
    }

    $x    = new static ();
    $x->S = $src;
    $src  = $x;

    return $x;
  }

  public static function fromCharCode (int $code): string
  {
    return mb_chr ($code);
  }

  /**
   * Creates an instance of `PowerString` that handles a copy of the given string.
   *
   * @param string $src
   * @return PowerString
   */
  public static function of (string $src = ''): static
  {
    $x    = new static ();
    $x->S = $src;

    return $x;
  }

  /**
   * Returns a singleton instance of `PowerString` that modifies the given string.
   * <p>**Warning:** this method returns **always** the same instance. This is meant to be a wrapper for applying
   * extension methods to an existing string variable. You should **not** store the instance anywhere, as it will lead
   * to unexpected problems. If  you need to do that, use {@see `PowerString`::of} instead.
   *
   * @param string $src
   * @return PowerString
   */
  public static function on (string &$src): static
  {
    static $x;

    if (!isset ($x)) {
      $x = new static ();
    }

    $x->S =& $src;

    return $x;
  }

  /**
   * Sets the `u` unicode flag on a regular expression and, if the `a` pseudo-flag is present, it removes it and returns
   * `true` to signal a global search (find all).
   *
   * @param string $pattern
   * @return bool true if the `a` flag was specified.
   */
  private static function toUnicodeRegex (string &$pattern): bool
  {
    if ($pattern === '') {
      throw new InvalidArgumentException ('Regular expression cannot be empty.');
    }

    $delimiter = $pattern[0];
    $parts     = explode ($delimiter, substr ($pattern, 1), 2);

    if (count ($parts) < 2) {
      throw new InvalidArgumentException ("Invalid regular expression '{$pattern}'.");
    }

    [$exp, $flags] = $parts;
    $count         = 0;
    $flags         = str_replace ('a', '', $flags, $count);
    $flags         = str_replace ('u', '', $flags) . 'u';
    $pattern       = $delimiter . $exp . $delimiter . $flags;

    return $count > 0;
  }

  public function __toString (): string
  {
    return $this->S;
  }

  /**
   * Appends the given string to the current one.
   *
   * @param string $str
   * @return $this
   */
  public function append (string $str): self
  {
    $this->S .= $str;

    return $this;
  }

  public function charAt (int $index): string
  {
    $v = mb_substr ($this->S, $index, 1);

    return $v === false ? '' : $v;
  }

  public function charCodeAt (int $index): int
  {
    $v = mb_substr ($this->S, $index, 1);

    return $v === false ? 0 : mb_ord ($v);
  }

  /**
   * Concatenates the given arguments to the current string.
   *
   * @param string|static ...$args
   */
  public function concat (string|self ...$args): self
  {
    foreach ($args as $arg) {
      $this->S .= (string) $arg;
    }

    return $this;
  }

  /**
   * Alias if {@see length()}.
   *
   * Additionaly, it allows you to call `count()` on a `PowerString` instance.
   * <p>Ex:
   * ```
   *   $s = PowerString::of ('test');
   *   echo count ($s);
   * ```
   * Outputs `4`.
   *
   * @return int
   */
  public function count (): int
  {
    return mb_strlen ($this->S);
  }

  public function endsWith (string $search, int $pos = 0): bool
  {
    $length = mb_strlen ($search);

    if ($length === 0) {
      return true;
    }

    $start = $pos === 0 ? mb_strlen ($this->S) - $length : $pos - $length;

    if ($start < 0) {
      return false;
    }

    return mb_substr ($this->S, $start, $length) === $search;
  }

  public function getIterator (): Traversable
  {
    return new ArrayIterator (mb_str_split ($this->S));
  }

  public function includes (string $search, int $from = 0): bool
  {
    return mb_strpos ($this->S, $search, $from) !== false;
  }

  public function indexOf (string $search, int $from = 0): int|false
  {
    return mb_strpos ($this->S, $search, $from);
  }

  /**
   * Searches for a pattern on a string and returns the index of the matched substring.
   *
   * ><p>This is a simpler version of {@see search}.
   *
   * @param string $pattern A regular expression pattern.
   * @return int The index of the matched substring.
   */
  public function indexOfPattern (string $pattern): int|false
  {
    $patternCopy = $pattern;
    self::toUnicodeRegex ($patternCopy);

    if (!preg_match ($patternCopy, $this->S, $matches, PREG_OFFSET_CAPTURE)) {
      return false;
    }

    return $matches[0][1];
  }

  public function lastIndexOf (string $search, int $from = 0): int|false
  {
    return mb_strrpos ($this->S, $search, $from);
  }

  public function length (): int
  {
    return mb_strlen ($this->S);
  }

  /**
   * @param string $pattern
   * @param int    $flags
   * @param int    $ofs
   * @return array|bool An array with the matches.
   */
  public function match (string $pattern, int $flags = 0, int $ofs = 0): array|false
  {
    $patternCopy = $pattern;
    $isGlobal    = self::toUnicodeRegex ($patternCopy);

    if ($isGlobal) {
      return preg_match_all ($patternCopy, $this->S, $matches, $flags, $ofs) ? $matches : false;
    }

    return preg_match ($patternCopy, $this->S, $matches, $flags, $ofs) ? $matches : false;
  }

  public function normalize (int $form = Normalizer::FORM_C): self
  {
    if (!class_exists (Normalizer::class)) {
      throw new RuntimeException ('ext-intl is required for PowerString::normalize');
    }

    $normalized = Normalizer::normalize ($this->S, $form);

    if ($normalized === false) {
      throw new InvalidArgumentException ('Unable to normalize string with the provided form.');
    }

    $this->S = $normalized;

    return $this;
  }

  public function offsetExists (mixed $offset): bool
  {
    try {
      $index = $this->resolveOffset ($offset, false);
    }
    catch (InvalidArgumentException) {
      return false;
    }

    $length = mb_strlen ($this->S);

    return $index >= 0 && $index < $length;
  }

  public function offsetGet (mixed $offset): string
  {
    $index = $this->resolveOffset ($offset, false);

    return $this->charAt ($index);
  }

  public function offsetSet (mixed $offset, mixed $value): void
  {
    $index       = $this->resolveOffset ($offset, true);
    $replacement = mb_substr ((string) $value, 0, 1);

    if ($replacement === false) {
      $replacement = '';
    }

    $length = mb_strlen ($this->S);

    if ($index >= $length) {
      $this->S .= $replacement;
      return;
    }

    $this->S = mb_substr ($this->S, 0, $index)
      . $replacement
      . mb_substr ($this->S, $index + 1);
  }

  public function offsetUnset (mixed $offset): void
  {
    $index = $this->resolveOffset ($offset, false);

    $this->S = mb_substr ($this->S, 0, $index)
      . mb_substr ($this->S, $index + 1);
  }

  /**
   * Prepends the given string to the current one.
   *
   * @param string $str
   * @return $this
   */
  public function prepend (string $str): self
  {
    $this->S = $str . $this->S;

    return $this;
  }

  public function repeat (int $count): self
  {
    $this->S = str_repeat ($this->S, $count);

    return $this;
  }

  public function replace (string $pattern, callable|string $replace): self
  {
    $patternCopy = $pattern;
    $limit       = self::toUnicodeRegex ($patternCopy) ? -1 : 1;

    if (is_callable ($replace)) {
      $result = preg_replace_callback ($patternCopy, $replace, $this->S, $limit);
    }
    else {
      $result = preg_replace ($patternCopy, $replace, $this->S, $limit);
    }

    if ($result === null) {
      throw new InvalidArgumentException ('Invalid regular expression in replace().');
    }

    $this->S = $result;

    return $this;
  }

  /**
   * Finds the position of the first occurrence of a pattern in the current string.
   *
   * ><p>This is an extended version of {@see indexOfPattern}.
   *
   * @param string $pattern A regular expression.
   * @param int    $from    The position where the search begins, counted from the beginning of the current string.
   * @param string $match   [optional] If a variable is specified, it will be set to the matched substring.
   * @return int|bool false if no match was found.
   */
  public function search (string $pattern, int $from = 0, ?string &$match = null): int|false
  {
    $patternCopy = $pattern;
    self::toUnicodeRegex ($patternCopy);

    if (preg_match ($patternCopy, $this->S, $matches, PREG_OFFSET_CAPTURE, $from)) {
      [$match, $offset] = $matches[0];

      return $offset;
    }

    return false;
  }

  public function slice (int $begin, ?int $end = null): self
  {
    $length = $end === null
      ? null
      : ($end < 0 ? $end : $end - $begin);

    $this->S = mb_substr ($this->S, $begin, $length);

    return $this;
  }

  /**
   * @param string $substr The substring to match and split on.
   * @param int    $limit  [optional]
   * @return PowerArray
   */
  public function split (string $substr, ?int $limit = null): PowerArray
  {
    $parts = $limit === null
      ? explode ($substr, $this->S)
      : explode ($substr, $this->S, $limit);

    return PowerArray::of ($parts);
  }

  /**
   * @param string $pattern A regular expression pattern.
   * @param int    $limit   [optional]
   * @return PowerArray
   */
  public function splitByPattern (string $pattern, int $limit = -1): PowerArray
  {
    $patternCopy = $pattern;
    self::toUnicodeRegex ($patternCopy);

    $parts = preg_split ($patternCopy, $this->S, $limit);

    if ($parts === false) {
      throw new InvalidArgumentException ('Invalid regular expression supplied to splitByPattern().');
    }

    return PowerArray::of ($parts);
  }

  /**
   * @param string $search
   * @param int    $pos [optional]
   * @return bool
   */
  public function startsWith (string $search, int $pos = 0): bool
  {
    return mb_substr ($this->S, $pos, mb_strlen ($search)) === $search;
  }

  public function substr (int $start, ?int $length = null): self
  {
    $this->S = $length === null
      ? mb_substr ($this->S, $start)
      : mb_substr ($this->S, $start, $length);

    return $this;
  }

  public function substring (int $indexA, ?int $indexB = null): self
  {
    $length = mb_strlen ($this->S);

    if ($indexB === null) {
      $indexB = $length;
    }

    if ($indexA > $indexB) {
      self::swapIndexes ($indexA, $indexB);
    }

    $indexA = max (0, $indexA);
    $indexB = max (0, $indexB);
    $indexA = min ($length, $indexA);
    $indexB = min ($length, $indexB);

    $this->S = mb_substr ($this->S, $indexA, $indexB - $indexA);

    return $this;
  }

  public function toLowerCase (): self
  {
    $this->S = mb_strtolower ($this->S);

    return $this;
  }

  public function toUpperCase (): self
  {
    $this->S = mb_strtoupper ($this->S);

    return $this;
  }

  public function trim (): self
  {
    $this->S = preg_replace ('/^\s+|\s+$/u', '', $this->S);

    return $this;
  }

  public function trimLeft (): self
  {
    $this->S = preg_replace ('/^\s+/u', '', $this->S);

    return $this;
  }

  public function trimRight (): self
  {
    $this->S = preg_replace ('/\s+$/u', '', $this->S);

    return $this;
  }

  private static function swapIndexes (int &$a, int &$b): void
  {
    $tmp = $a;
    $a   = $b;
    $b   = $tmp;
  }

  private function resolveOffset (mixed $offset, bool $allowEnd): int
  {
    if (is_int ($offset)) {
      $index = $offset;
    }
    elseif (is_string ($offset) && preg_match ('/^-?\d+$/', $offset)) {
      $index = (int) $offset;
    }
    else {
      throw new InvalidArgumentException ('String offsets must be integers.');
    }

    $length = mb_strlen ($this->S);

    if ($index < 0) {
      $index += $length;
    }

    if ($index < 0) {
      throw new InvalidArgumentException ('Offset is out of bounds.');
    }

    if ($index > $length || (!$allowEnd && $index === $length)) {
      throw new InvalidArgumentException ('Offset is out of bounds.');
    }

    return $index;
  }
}
