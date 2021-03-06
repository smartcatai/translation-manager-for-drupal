<?php

namespace Drupal\smartcat_translation_manager\DB\Repository;

/**
 * Abstract table repository.
 */
abstract class RepositoryAbstract implements RepositoryInterface {

  const TABLE_PREFIX = 'smartcat_translation_manager_';

  protected $connection;

  /**
   * Init dependencies.
   */
  public function __construct() {
    $this->connection = \Drupal::database();
  }

  /**
   * Count rows in table.
   *
   * @return int $count
   */
  public function getCount() {
    $table_name = $this->getTableName();
    $count = $this->connection->query("SELECT COUNT(*) FROM $table_name");

    return $count;
  }

  private $persists = [];

  /**
   *
   */
  public function persist($o) {
    $this->persists[] = $o;
  }

  /**
   *
   */
  abstract protected function doFlush(array $persists);

  /**
   *
   */
  public function flush() {
    $this->doFlush($this->persists);
    $this->persists = [];
  }

  /**
   *
   */
  abstract protected function toEntity($row);

  /**
   *
   */
  protected function prepareResult($rows) {
    $result = [];
    foreach ($rows as $row) {
      $result[] = $this->toEntity($row);
    }

    return $result;
  }

  /**
   * @param array $criterias
   *
   * @return mixed
   */
  public function getOneBy(array $criterias) {
    $table_name = $this->getTableName();
    $query = $this->connection->select($table_name, 's')
      ->fields('s');

    foreach ($criterias as $key => $value) {
      $query->condition($key, $value);
    }

    $row = $query->execute()->fetchObject();
    return $row ? $this->toEntity($row) : NULL;
  }

  /**
   * @param array $criterias
   * @param int $offset
   * @param int $limit
   * @return array
   */
  public function getBy(array $criterias = [], int $offset = 0, int $limit = 10, array $order = []) {
    $table_name = $this->getTableName();
    $query = $this->connection->select($table_name, 's')
      ->fields('s');

    if (!empty($criterias)) {
      foreach ($criterias as $key => $value) {
        if (is_array($value)) {
          $query->condition($key, $value[0], $value[1]);
          continue;
        }
        $query->condition($key, $value);
      }
    }
    if (!empty($order)) {
      foreach ($order as $field => $direction) {
        $query->orderBy($field, $direction);
      }
    }

    $query->range($offset, $limit);

    $result = $query->execute();
    $entities = [];
    foreach ($result as $record) {
      $entities[] = $this->toEntity($record);
    }
    return $entities;
  }

  /**
   * @param array $criterias
   * @param int $offset
   * @param int $limit
   * @return array
   */
  public function count(array $criterias = []) {
    $table_name = $this->getTableName();
    $query = $this->connection->select($table_name, 's')
      ->fields('s');

    if (!empty($criterias)) {
      foreach ($criterias as $key => $value) {
        if (is_array($value)) {
          $query->condition($key, $value[0], $value[1]);
          continue;
        }
        $query->condition($key, $value);
      }
    }

    return $query->countQuery()->execute()->fetchField();
  }

  /**
   *
   */
  public function bulkUpdate($data, $criterias) {
    $table_name = $this->getTableName();
    $query = $this->connection->update($table_name)
      ->fields($data);

    if (empty($criterias)) {
      return FALSE;
    }

    foreach ($criterias as $key => $value) {
      if (is_array($value)) {
        $query->condition($key, $value[0], $value[1]);
        continue;
      }
      $query->condition($key, $value);
    }

    try {
      return $query->execute();
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

}
