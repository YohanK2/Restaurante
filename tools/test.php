<?php
require_once __DIR__ . '/../backend/core/auth.php';

echo "<pre>";
echo "SCRIPT_NAME: " . $_SERVER['SCRIPT_NAME'] . PHP_EOL;
echo "dirname(): " . dirname($_SERVER['SCRIPT_NAME']) . PHP_EOL;
echo "getBaseUrl(): " . getBaseUrl() . PHP_EOL;