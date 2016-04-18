<?php

class Map implements \IteratorAggregate, \ArrayAccess, \Serializable, \Countable
{
  private $data = [];

  function __debugInfo ()
  {
    return $this->data;
  }

  function __get ($key)
  {
    return isset($this->data[$key]) ? $this->data[$key] : null;
  }

  function __set ($key, $value)
  {
    if (isset($value))
      $this->data[$key] = $value;
    else unset ($this->data[$key]);
  }

  function __isset ($key)
  {
    return isset($this->data[$key]);
  }

  function __unset ($key)
  {
    unset ($this->data[$key]);
  }

  public function & asArray ()
  {
    return $this->data;
  }

  /**
   * @return $this
   */
  function clear ()
  {
    $this->data = [];
    return $this;
  }

  public function count ()
  {
    return count ($this->data);
  }

  public function getIterator ()
  {
    return new \ArrayIterator($this->data);
  }

  public function keys ()
  {
    return array_keys ($this->data);
  }

  /**
   * @param Map|array|\IteratorAggregate|object $data
   * @return $this
   */
  public function merge ($data)
  {
    if (is_array ($data))
      $this->data = $data + $this->data;
    elseif ($data instanceof static)
      $this->data = $data->data + $this->data;
    elseif ($data instanceof \IteratorAggregate)
      $this->data = iterator_to_array ($data->getIterator (), true) + $this->data; // optimized for speed, not memory
    else if (is_object ($data))
      $this->data = get_object_vars ($data) + $this->data;
    else throw new \InvalidArgumentException('Unsupported type ' . gettype ($data));
    return $this;
  }

  public function offsetExists ($offset)
  {
    return isset($this->data[$offset]);
  }

  public function offsetGet ($offset)
  {
    return isset($this->data[$offset]) ? $this->data[$offset] : null;
  }

  public function offsetSet ($offset, $value)
  {
    $this->data[$offset] = $value;
  }

  public function offsetUnset ($offset)
  {
    unset ($this->data[$offset]);
  }

  public function serialize ()
  {
    return serialize ($this->data);
  }

  /**
   * @param Map|array|\IteratorAggregate|object $data
   * @return $this
   */
  public function set ($data)
  {
    if (is_array ($data))
      $this->data = $data;
    else {
      $this->data = [];
      $this->merge ($data);
    }
    return $this;
  }

  public function unserialize ($serialized)
  {
    $this->data = unserialize ($serialized);
  }

}
