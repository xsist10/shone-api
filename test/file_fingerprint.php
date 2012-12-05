#!/usr/bin/php
<?php

require_once '../lib/ShoneSecurity.php';
require_once 'config.php';

$sFileName = ($argc > 1 ? $argv[1] : '/path/to/file');

try
{
    $oSecurity = new ShoneSecurity($sKey);
    $aResult = $oSecurity->file_fingerprint($sFileName);
    print_r($aResult);
}
catch (ShoneSecurityException $oException)
{
    echo "Failed: " . $oException->getMessage() . "\n";
}