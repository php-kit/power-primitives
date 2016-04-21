<?php

interface MapInterface extends IteratorAggregate, ArrayAccess, Serializable, Countable
{
  /**
   * Merges data into the Map.
   *
   * @param Map|array|\IteratorAggregate|object $data
   * @return $this
   */
  public function apply ($data);

  /**
   * Retrieves the map's content as an associative array.
   *
   * @return array
   */
  public function asArray ();

  /**
   * Clears the map's content.
   *
   * @return $this
   */
  function clear ();

  /**
   * Returns a list of the map's keys.
   *
   * @return string[]
   */
  public function keys ();

  /**
   * Replaces the map's content with the given data.
   *
   * @param Map|array|\IteratorAggregate|object $data
   * @return $this
   */
  public function set ($data);
}
