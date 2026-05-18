<?php

namespace machbarmacher\GdprDump\ColumnTransformer;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event dispatched when a column value needs to be transformed.
 */
class ColumnTransformEvent extends Event {

  /**
   * The table name.
   *
   * @var string
   */
  protected $table;

  /**
   * The column name.
   *
   * @var string
   */
  protected $column;

  /**
   * The transformation expression.
   *
   * @var mixed
   */
  protected $expression;

  /**
   * Whether a replacement value has been set.
   *
   * @var bool
   */
  protected $isReplacementSet = FALSE;

  /**
   * The replacement value.
   *
   * @var mixed
   */
  protected $replacementValue;

  /**
   * ColumnTransformEvent constructor.
   */
  public function __construct($table, $column, $expression) {
    $this->table = $table;
    $this->column = $column;
    $this->expression = $expression;
  }

  /**
   * Sets the replacement value for this column.
   */
  public function setReplacementValue($value) {
    $this->isReplacementSet = TRUE;
    $this->replacementValue = $value;
  }

  /**
   * Returns the table name.
   */
  public function getTable() {
    return $this->table;
  }

  /**
   * Returns the column name.
   */
  public function getColumn() {
    return $this->column;
  }

  /**
   * Returns the transformation expression.
   */
  public function getExpression() {
    return $this->expression;
  }

  /**
   * Returns whether a replacement value has been set.
   */
  public function isReplacementSet() {
    return $this->isReplacementSet;
  }

  /**
   * Returns the replacement value.
   */
  public function getReplacementValue() {
    return $this->replacementValue;
  }

}
