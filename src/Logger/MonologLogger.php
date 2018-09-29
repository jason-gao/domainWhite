<?php
/**
 * Desc: log interface
 * Created by PhpStorm.
 * User: jasong
 * Date: 2018/09/29 16:38
 */

namespace DomainWhiteSdk\Logger;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class MonologLogger
{

    private static $logger;


    public static function getLoggerInstance($name = 'default', $stream_log = '/tmp/log_demo.log', $level = Logger::DEBUG)
    {
        if (!isset(self::$logger)) {
            // Create the logger
            self::$logger = new Logger($name);
            // Now add some handlers
            $streamHandler = new StreamHandler($stream_log, $level);
            $streamHandler->setFormatter(new LineFormatter("[%datetime%] %channel%.%level_name%: %message% %context% %extra% \n", '', true));
            self::$logger->pushHandler($streamHandler);
            $error_handler = new ErrorLogHandler(4, $level, true, true);
            $error_handler->setFormatter(new LineFormatter("[%datetime%] %channel%.%level_name%: %message% %context% %extra% \n", '', true));
            self::$logger->pushHandler($error_handler);
        }

        return self::$logger;
    }


}