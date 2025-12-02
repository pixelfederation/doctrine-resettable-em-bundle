<?php

declare(strict_types=1);

use Symfony\Component\ErrorHandler\ErrorHandler;
use Symfony\Component\Filesystem\Filesystem;

require dirname(__DIR__) . '/vendor/autoload.php';

set_exception_handler([new ErrorHandler(), 'handleException']);

(new Filesystem())->remove(dirname(__DIR__) . '/tests/TestApplication/var/cache');
