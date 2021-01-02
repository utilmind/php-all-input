<?php
/*!
 * all-input.php 0.0.1
 * http://github.com/utilmind/php-all-input/
 *
 * This script can be used to catch all received input/POST/GET + server variables.
 *
 * @license Copyright 2021. All rights reserved.
 * @author: Oleksii Kuznietsov, utilmind@gmail.com
 */


/* !! WARNING: Use it carefully and only for directories with temporary files.
   In case if you don't plan to delete files inside with crontab -- use mkdir() intead.
*/
function mkdirr($dir, $mode = 0777) { // recursive mkdir. Unfortunately mkdir doesn't set correct directory permissions.
  if (!is_dir($dir)) {
    $m = umask(0); // save rights
    $r = @mkdir($dir, $mode, 1);
    umask($m); // restore rights
    return $r;
  }
  return true; // it's already there
}

function write_file($fn, $s, $mode = 'w',  // set mode to 'a' for append. If $s is array, data will be exported as CSV. But array MUST BE 2-DIMENSIONAL! Others not supported!
    $csv_delimiter = false, $csv_enclosure = false) {
  mkdirr(dirname($fn)); // unfortunately standard mkdir() doesn't creates correct permission
  if ($f = fopen($fn, $mode)) { // remember about directory permissions in PHP's safe mode!
    flock($f, LOCK_EX);
    if (is_array($s)) {
      fwrite($f, "\xEF\xBB\xBF"); // UTF-8 BOM
      $r = 0;
      foreach ($s as $line)
        $r+= fputcsv($f, $line, $csv_delimiter, $csv_enclosure);
    }else {
      $r = ($r = fwrite($f, $s)) === strlen($s) ? $r : false; // check if all data written
    }
    flock($f, LOCK_UN);
    fclose($f);
    @chmod($fn, 0777);
    return $r;
  }
  return false;
}

// Converts 2-dimensional array into string with Keys and Values separated with "=".
function key_val_arr2list($arr, // $arr is 2-dimensional array with Keys and Values.
     $esc_callback = null, // callback function used to escape values. Eg mdb::esc() or url_encode(), etc.
     $sep = false) { // "," by default
  if (!is_array($arr)) return false;
  if (!$sep) $sep = ',';
  $q = false;
  foreach ($arr as $k => $v)
    $q.= ($q ? $sep : '').$k.'="'.(is_callable($esc_callback) ? call_user_func($esc_callback, $v) : $v).'"';
  return $q;
}


$input = file_get_contents('php://input');
$post = isset($_POST) ? key_val_arr2list($_POST, null, "\n") : false;
$get = isset($_GET) ? key_val_arr2list($_GET, null, "\n") : false;
$server = isset($_SERVER) ? key_val_arr2list($_SERVER, null, "\n") : false;

$out = ($input ? "INPUT:\n$input\n\n\n" : '').
       ($post ? "POST:\n$post\n\n\n" : '').
       ($get ? "GET:\n$get\n\n\n" : '').
       ($server ? "SERVER:\n$server\n\n\n" : '');


write_file('./all-input.txt', $out, 'a');
