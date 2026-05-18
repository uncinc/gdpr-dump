<?php

use machbarmacher\GdprDump\ColumnTransformer\ColumnTransformer;

use PHPUnit\Framework\TestCase;

/**
 * Tests for the ColumnTransformer class.
 */
class ColumnTransformerTest extends TestCase {

  /**
   * JSON fixture data for tests.
   *
   * @var string
   */
  private $jsonData = '{"users_field_data":{"name":"uid","mail":{"formatter":"clear"},"init":"uid","pass":{"transformer":"faker", "formatter":"password"}}}';

  /**
   * Tests that a faker statement produces a string result.
   */
  public function testCreatingNewFakerStatement() {
    $gdprExpressions = json_decode($this->jsonData, TRUE);
    $tableName = "users_field_data";
    $columnName = "pass";
    $result = ColumnTransformer::replaceValue($tableName, $columnName, $gdprExpressions[$tableName][$columnName], 'en_US');
    $this->assertTrue(is_string($result));
  }

  /**
   * Tests that a clear statement produces an empty string result.
   */
  public function testCreatingNewClearStatement() {
    $gdprExpressions = json_decode($this->jsonData, TRUE);
    $tableName = "users_field_data";
    $columnName = "mail";
    $result = ColumnTransformer::replaceValue($tableName, $columnName, $gdprExpressions[$tableName][$columnName], 'en_US');
    $this->assertTrue(is_string($result) && strlen($result) == 0);
  }

}
