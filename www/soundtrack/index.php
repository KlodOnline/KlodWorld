<?php
header('Content-Type: text/html; charset=utf-8');

$dir = __DIR__;
$files = scandir($dir);

foreach ($files as $file) {
    if (is_file("$dir/$file") && preg_match('/\.mp3$/i', $file)) {
        echo "<a href=".$file.">".$file . "</a><br/>\n";
    }
}
