<?php

namespace machbarmacher\GdprDump\ColumnTransformer;

use Symfony\Component\EventDispatcher\EventDispatcher;
use machbarmacher\GdprDump\ColumnTransformer\Plugins\ClearColumnTransformer;
use machbarmacher\GdprDump\ColumnTransformer\Plugins\FakerColumnTransformer;

/**
 * Abstract base class for column value transformers.
 */
abstract class ColumnTransformer {

  const COLUMN_TRANSFORM_REQUEST = "columntransform.request";

  /**
   * The table name.
   *
   * @var string
   */
  private $tableName;

  /**
   * The column name.
   *
   * @var string
   */
  private $columnName;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcher
   */
  protected static $dispatcher;

  /**
   * Sets up the event dispatcher with registered transformer listeners.
   */
  public static function setUp($locale) {
    if (!isset(self::$dispatcher)) {
      self::$dispatcher = new EventDispatcher();

      self::$dispatcher->addListener(self::COLUMN_TRANSFORM_REQUEST,
          new FakerColumnTransformer($locale));
      self::$dispatcher->addListener(self::COLUMN_TRANSFORM_REQUEST,
          new ClearColumnTransformer());
    }

  }

  /**
   * Replaces a column value using the matching transformer.
   */
  public static function replaceValue($tableName, $columnName, $expression, $locale) {
    self::setUp($locale);

    if ($expression) {
      $event = new ColumnTransformEvent($tableName, $columnName, $expression);
      self::$dispatcher->dispatch($event, self::COLUMN_TRANSFORM_REQUEST);

      if ($event->isReplacementSet()) {
        return $event->getReplacementValue();
      }
    }

    return FALSE;
  }

  /**
   * Invokes the transformer if the expression formatter is supported.
   */
  public function __invoke(ColumnTransformEvent $event) {
    if (in_array(($event->getExpression())['formatter'],
        $this->getSupportedFormatters())) {
      $event->setReplacementValue($this->getValue($event->getExpression()));
    }
  }

  /**
   * Returns the transformed value for the given expression.
   */
  abstract public function getValue($expression);

  /**
   * Returns the list of formatter names this transformer supports.
   */
  abstract protected function getSupportedFormatters();

}
