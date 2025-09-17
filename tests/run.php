#!/usr/bin/env php
<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/PowerArrayTest.php';
require __DIR__ . '/PowerStringTest.php';
require __DIR__ . '/GlobalsTest.php';

$suite = new TestSuite();

new PowerArrayTest($suite);
new PowerStringTest($suite);
new GlobalsTest($suite);

exit($suite->run() ? 0 : 1);
