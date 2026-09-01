<?php
/**
 * Temporary, token-gated maintenance endpoint. Restores the homepage files that
 * an empty Cloudways deploy_path overwrote. Deletes itself on ?a=selfdestruct.
 * Not part of the demo site. Remove immediately after use.
 */
$H = '4d149585e9f00a1de0958d093343931351630c34d3235ac343f77a8f2b90fc1f';
$t = isset($_REQUEST['t']) ? (string)$_REQUEST['t'] : '';
if (!hash_equals($H, hash('sha256', $t))) { http_response_code(404); exit; }
header('Content-Type: text/plain; charset=utf-8');
header('X-Robots-Tag: noindex');

$root = realpath(__DIR__ . '/..');           // public_html
$a = isset($_REQUEST['a']) ? $_REQUEST['a'] : 'ls';

function safe($root, $rel) {
  $p = $root . '/' . ltrim($rel, '/');
  $r = realpath($p);
  if ($r === false) return null;
  return (strpos($r, $root) === 0) ? $r : null;
}

if ($a === 'ls') {
  $rel = isset($_REQUEST['p']) ? $_REQUEST['p'] : '';
  $d = safe($root, $rel);
  if (!$d || !is_dir($d)) { echo "no such dir\n"; exit; }
  foreach (scandir($d) as $f) {
    if ($f === '.' || $f === '..') continue;
    $fp = $d . '/' . $f;
    printf("%-46s %-4s %10d  %s\n", $f, is_dir($fp) ? 'dir' : 'file', filesize($fp), date('Y-m-d H:i:s', filemtime($fp)));
  }
  exit;
}

if ($a === 'get') {
  $f = safe($root, isset($_REQUEST['p']) ? $_REQUEST['p'] : '');
  if (!$f || !is_file($f)) { echo "no such file\n"; exit; }
  echo base64_encode(file_get_contents($f));
  exit;
}

if ($a === 'put') {
  $rel = isset($_POST['p']) ? $_POST['p'] : '';
  $allow = array('index.html', 'robots.txt', 'sitemap.xml', '404.html');
  if (!in_array($rel, $allow, true)) { echo "target not allowed\n"; exit; }
  $data = base64_decode(isset($_POST['d']) ? $_POST['d'] : '', true);
  if ($data === false || $data === '') { echo "bad payload\n"; exit; }
  $dest = $root . '/' . $rel;
  if (is_file($dest)) @copy($dest, $dest . '.pre-restore-' . date('Ymd-His'));
  $n = file_put_contents($dest, $data);
  echo "wrote $rel bytes=$n sha256=" . hash('sha256', $data) . "\n";
  exit;
}

if ($a === 'selfdestruct') { $ok = @unlink(__FILE__); echo $ok ? "gone\n" : "could not delete\n"; exit; }
echo "unknown action\n";
