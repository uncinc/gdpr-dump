<?php

namespace machbarmacher\GdprDump\ColumnTransformer\Plugins;

use machbarmacher\GdprDump\ColumnTransformer\ColumnTransformer;

/**
 * Transformer that clears a column value by returning an empty string.
 */
class ClearColumnTransformer extends ColumnTransformer {

  /**
   * Returns the supported formatter names.
   */
  protected function getSupportedFormatters() {
    return ['clear'];
  }

  /**
   * Returns an empty string as the replacement value.
   */
  public function getValue($expression) {
    return "";
  }

}
