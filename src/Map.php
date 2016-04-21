<?php

class Map implements MapInterface
{
  private $_data = [];

  function __debugInfo ()
  {
    return $this->_data;
  }

  function __get ($key)
  {
    return isset($this->_data[$key]) ? $this->_data[$key] : null;
  }

  function __set ($key, $value)
  {
    if (isset($value))
      $this->_data[$key] = $value;
    else unset ($this->_data[$key]);
  }

  function __isset ($key)
  {
    return isset($this->_data[$key]);
  }

  function __unset ($key)
  {
    unset ($this->_data[$key]);
  }

  public function & asArray ()
  {
    return $this->_data;
  }

  function clear ()
  {
    $this->_data = [];
    return $this;
  }

  public function count ()
  {
    return count ($this->_data);
  }

  public function getIterator ()
  {
    return new \ArrayIterator($this->_data);
  }

  public function keys ()
  {
    return array_keys ($this->_data);
  }

  public function offsetExists ($offset)
  {
    return isset($this->_data[$offset]);
  }

  public function offsetGet ($offset)
  {
    return isset($this->_data[$offset]) ? $this->_data[$offset] : null;
  }

  public function offsetSet ($offset, $value)
  {
    $this->_data[$offset] = $value;
  }

  public function offsetUnset ($offset)
  {
    unset ($this->_data[$offset]);
  }

  public function serialize ()
  {
    return serialize ($this->_data);
  }

  public function apply ($data)
  {
    if (is_array ($data))
      $this->_data = $data + $this->_data;
    elseif ($data instanceof self)
      $this->_data = $data->_data + $this->_data;
    elseif ($data instanceof \IteratorAggregate)
      $this->_data = iterator_to_array ($data->getIterator (), true) + $this->_data; // optimized for speed, not memory
    else if (is_object ($data))
      $this->_data = get_object_vars ($data) + $this->_data;
    else throw new \InvalidArgumentException('Unsupported type ' . gettype ($data));
    return $this;
  }

  public function set ($data)
  {
    if (is_array ($data))
      $this->_data = $data;
    else {
      $this->_data = [];
      $this->apply ($data);
    }
    return $this;
  }

  public function unserialize ($serialized)
  {
    $this->_data = unserialize ($serialized);
  }

}
