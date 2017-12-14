<?php
namespace legtrack;

function loadConfig($file) {
  if (!file_exists($file)) die("Config Not Found: " . $file . PHP_EOL);
  require_once $file;
}

function loadEnv($env) {
  if (!$env) $env = 'development';
  $path = "./config/" . $env . ".php";
  if (!file_exists($path)) die("Config Not Found: " . $path . PHP_EOL);
  require_once $path;
}

function fileName($year, $measure) {
  return $year . "_" . $measure . ".html";
}

function getResultPath($year, $measure) {
  return "./results/".fileName($year, $measure);
}

function saveContents($year, $measure, $data) {
  $file = getResultPath($year, $measure);
  file_put_contents($file, $data);
}

function getContents($year, $measure) {
  $file = getResultPath($year, $measure);
  return file_get_contents($file);
}

function getMd5($str) {
  return md5($str);
}

function getFileMd5($year, $measure) {
  $file = getResultPath($year, $measure);
  return md5_file($file);
}

?>
