#!/usr/bin/php
<?php

require_once '../lib/ShoneSecurity.php';
require_once 'config.php';

$sFolder = ($argc > 1 ? $argv[1] : '/path/to/folder');

try
{
    $oSecurity = new ShoneSecurity($sKey);
    $sHash = $oSecurity->submit($sFolder);
    echo "Hash: " . $sHash . "\n";

    // Sleep for 2 minutes and then attempt to get the result
    sleep(120);

    $aResult = $oSecurity->get_job($sHash);
    print_r($aResult);
}
catch (ShoneSecurityException $oException)
{
    echo "Failed: " . $oException->getMessage() . "\n";
}