<?php

namespace Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
error_reporting(E_ERROR);
date_default_timezone_set('Asia/Shanghai');

abstract class TestCase extends BaseTestCase
{
}
