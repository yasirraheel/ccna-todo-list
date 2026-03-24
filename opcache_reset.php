<?php
header('Content-Type: text/plain; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
$ok = false;
if (function_exists('opcache_reset')) {
    $ok = @opcache_reset();
}
echo $ok ? 'OK' : 'NO OPCACHE';

