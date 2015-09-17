<?php

/**
 * Creates a new instance of `PowerArray` from the given array.
 * @param array $a
 * @return PowerArray
 */

function PA (array $a = [])
{
  return PowerArray::of ($a);
}

/**
 * Reuses the same singleton instance of `PowerArray` to wrap the given array.
 *
 * This has increased performance, but it should be used with care.
 * @param array $a Variable.
 * @return PowerArray
 */
function asPA (array & $a)
{
  return PowerArray::on ($a);
}

/**
 * Converts an array variable to an instance of `PowerArray`.
 * @param array $a Variable.
 * @return PowerArray
 */
function toPA (array & $a)
{
  return PowerArray::cast ($a);
}

/**
 * Creates a new instance of `PowerString` from the given string.
 * @param string $str
 * @return PowerString
 */
function PS ($str = '')
{
  return PowerString::of ($str);
}

/**
 * Reuses the same singleton instance of `PowerString` to wrap the given string.
 *
 * This has increased performance, but it should be used with care.
 * @param string $str Variable.
 * @return PowerString
 */
function asPS (& $str)
{
  return PowerString::on ($str);
}

/**
 * Converts a string variable to an instance of `PowerString`.
 * @param string $str Variable.
 * @return PowerString
 */
function toPS (& $str)
{
  return PowerString::cast ($str);
}
