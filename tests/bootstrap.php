<?php

require __DIR__ . '/../vendor/autoload.php';

define('_PS_IN_TEST_', true);
define('_PS_ROOT_DIR_', __DIR__ . '/../..');
define('_PS_MODULE_DIR_', _PS_ROOT_DIR_ . '/tests/Resources/modules/');
if (!defined('__PS_BASE_URI__')) {
    define('__PS_BASE_URI__', '');
}
