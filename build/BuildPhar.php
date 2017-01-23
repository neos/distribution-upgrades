#!/usr/bin/env php
<?php

$phar = new Phar('DistributionUpgrader.phar');
$phar->startBuffering();
$phar->buildFromDirectory(dirname(__FILE__) . '/../src');

$stub = <<<EOT
#!/usr/bin/env php
<?php
Phar::mapPhar('DistributionUpgrader.phar');
Phar::interceptFileFuncs();
require('phar://DistributionUpgrader.phar/Command.php');
__HALT_COMPILER();

EOT;

$phar->setStub($stub);
$phar->stopBuffering();
