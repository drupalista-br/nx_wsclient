<?php
namespace NXWSClient;

use RecursiveIteratorIterator,
    RecursiveDirectoryIterator,
    FilesystemIterator,
    Exception;

class tools {
  const COLOR_WHITE = 'white';
  const COLOR_YELLOW = 'yellow';
  const COLOR_BLUE = 'blue';
  const COLOR_GREEN = 'green';
  const COLOR_RED = 'red';

  /**
   * Converts a message string into a color coded string.
   * 
   * @param Array $msg
   *   $msg[0] is the message to print.
   *   $msg[1] are the placeholders array for substitution.
   *   Example:
   *   $msg = array("Hello %ph\n", array('%ph' => 'World'));
   *
   * @param String $color
   *   The color name. Available values are:
   *   white, yellow, blue, green and red.
   *
   * @return String
   *   The color coded message.
   */
  static public function color_msg($msg, $color) {
    $white = "\033[0m";
    $yellow = "\033[1;33m";
    $blue = "\033[1;36m";
    $green = "\033[1;32m";
    $red = "\033[1;31m";

    // $msg[0] is the message to print.
    // $msg[1] are the placeholders array.
    if (isset($msg[0])) {
      if (isset($msg[1]) && is_array($msg[1])) {
        // Replace placeholder values.
        foreach($msg[1] as $placeholder => $ph_value) {
          // Turn all text variables in blue.
          $ph_value = $blue . $ph_value . ${$color};
          $msg[0] = str_replace($placeholder, $ph_value, $msg[0]);
        }
      }

      return ${$color} . $msg[0] . $white;
    }
    else {
      throw new Exception("$msg should be sent into an array.\n $warning_location");
    }
  }

  /**
   * Print out a yellow message on the terminal console.
   *
   * @param String $msg
   *   The message to be printed out.
   *
   * @param Array $place_holders
   *   It's keys are the placeholders and it's values are the replacement
   *   value.
   *
   * @return String
   *   The color coded string.
   */
  static public function print_green($msg, $place_holders = array()) {
    $msg .= PHP_EOL;
    print tools::color_msg(array($msg, $place_holders), tools::COLOR_GREEN);
  }

  static public function print_yellow($msg, $place_holders = array()) {
    $msg .= PHP_EOL;
    print tools::color_msg(array($msg, $place_holders), tools::COLOR_YELLOW);
  }

  static public function print_red($msg, $place_holders = array()) {
    $msg .= PHP_EOL;
    print tools::color_msg(array($msg, $place_holders), tools::COLOR_RED);
  }

  static public function print_blue($msg, $place_holders = array()) {
    $msg .= PHP_EOL;
    print tools::color_msg(array($msg, $place_holders), tools::COLOR_BLUE);
  }

  /**
   * Remove a folder recursively.
   *
   * @param String $dir_path
   *   The folder's path.
   */
  static public function rmdirr($dir_path) {
    foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir_path, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $path) {
      $path->isDir() && !$path->isLink() ? rmdir($path->getPathname()) : unlink($path->getPathname());
    }
    rmdir($dir_path);
  }
}
