#!/usr/bin/php
<?php

include '../lib/ShoneSecurity.php';

$oSecurity = new ShoneSecurity('a84b00e68223766724ae527dcea6d7e7a8733768');
$aResult = $oSecurity->file_fingerprint('CodeIgniter_1.0b/index.php');
print_r($aResult);