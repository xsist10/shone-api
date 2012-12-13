#!/usr/bin/php
<?php

require_once '../lib/ShoneSecurity.php';
require_once 'config.php';

try
{
    $oSecurity = new ShoneSecurity($sKey);
    $aResult = $oSecurity->get_status_by_hash('hash');
    print_r($aResult);
    $aResult = $oSecurity->get_status_by_label('label');
    print_r($aResult);
}
catch (ShoneSecurityException $oException)
{
    echo "Failed: " . $oException->getMessage() . "\n";
}
