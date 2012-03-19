#!/usr/bin/php
<?php

require_once '../lib/ShoneSecurity.php';
require_once 'config.php';

try
{
    $oSecurity = new ShoneSecurity($sKey);
    $aResult = $oSecurity->file_fingerprint('CodeIgniter_1.1b/index.php');
    print_r($aResult);
}
catch (ShoneSecurityException $oException)
{
    echo "Failed: " . $oException->getMessage() . "\n";
}