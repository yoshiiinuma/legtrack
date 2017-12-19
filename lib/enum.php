<?php
namespace legtrack;

class Enum {
  static function getMeasureTypes() {
    return (object)array(
      'hb'  => 1,
      'sb'  => 2,
      'hr'  => 3,
      'sr'  => 4,
      'hcr' => 5,
      'scr' => 6,
      'gm'  => 7,
    );
  }

  static function getJobStatus() {
    return (object)array(
      'started'  => 1,
      'skipped'  => 2,
      'failed'  => 3,
      'completed'  => 4,
    );
  }
}

?>
