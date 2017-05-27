<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace common\components\TaskQueue;

use Yii;
use common\components\TaskQueue\Job;

class Application extends \yii\base\Application
{
    /**
     * @inheritdoc
     */
    public function coreComponents()
    {
        return array_merge(parent::coreComponents(), [
            'request' => ['class' => 'yii\console\Request'],
            'response' => ['class' => 'yii\console\Response'],
            'errorHandler' => ['class' => 'yii\console\ErrorHandler'],
        ]);
    }
    /**
     * Returns the error handler component.
     * @return ErrorHandler the error handler application component.
     */
    public function getErrorHandler()
    {
        return $this->get('errorHandler');
    }

    /**
     * Returns the request component.
     * @return Request the request component.
     */
    public function getRequest()
    {
        return $this->get('request');
    }

    /**
     * Returns the response component.
     * @return Response the response component.
     */
    public function getResponse()
    {
        return $this->get('response');
    }

    /**
     * Handles the specified request.
     * @param Request $request the request to be handled
     * @return Response the resulting response
     */
    public function handleRequest($request)
    {

        if (!class_exists('Composer\Autoload\ClassLoader', false)) {
            die(
                'You need to set up the project dependencies using the following commands:' . PHP_EOL .
                    'curl -s http://getcomposer.org/installer | php' . PHP_EOL .
                    'php composer.phar install' . PHP_EOL
            );
        }

        $QUEUE = getenv('QUEUE');
        if(empty($QUEUE)) {
            die("Set QUEUE env var containing the list of queues to work.\n");
        }

        /**
         * REDIS_BACKEND can have simple 'host:port' format or use a DSN-style format like this:
         * - redis://user:pass@host:port
         *
         * Note: the 'user' part of the DSN URI is required but is not used.
         */
        $REDIS_BACKEND = getenv('REDIS_BACKEND');

        // A redis database number
        $REDIS_BACKEND_DB = getenv('REDIS_BACKEND_DB');
        if(!empty($REDIS_BACKEND)) {
            if (empty($REDIS_BACKEND_DB))
                \Resque::setBackend($REDIS_BACKEND);
            else
                \Resque::setBackend($REDIS_BACKEND, $REDIS_BACKEND_DB);
        }

        $logLevel = false;
        $LOGGING = getenv('LOGGING');
        $VERBOSE = getenv('VERBOSE');
        $VVERBOSE = getenv('VVERBOSE');
        if(!empty($LOGGING) || !empty($VERBOSE)) {
            $logLevel = true;
        }
        else if(!empty($VVERBOSE)) {
            $logLevel = true;
        }

        $APP_INCLUDE = getenv('APP_INCLUDE');
        if($APP_INCLUDE) {
            if(!file_exists($APP_INCLUDE)) {
                die('APP_INCLUDE ('.$APP_INCLUDE.") does not exist.\n");
            }

            require_once $APP_INCLUDE;
        }

        // See if the APP_INCLUDE containes a logger object,
        // If none exists, fallback to internal logger
        if (!isset($logger) || !is_object($logger)) {
            $logger = new \Resque_Log($logLevel);
        }

        $BLOCKING = getenv('BLOCKING') !== FALSE;

        $interval = 5;
        $INTERVAL = getenv('INTERVAL');
        if(!empty($INTERVAL)) {
            $interval = $INTERVAL;
        }

        $count = 1;
        $COUNT = getenv('COUNT');
        if(!empty($COUNT) && $COUNT > 1) {
            $count = $COUNT;
        }

        $PREFIX = getenv('PREFIX');
        if(!empty($PREFIX)) {
            $logger->log(\Psr\Log\LogLevel::INFO, 'Prefix set to {prefix}', array('prefix' => $PREFIX));
            \Resque_Redis::prefix($PREFIX);
        }

        if($count > 1) {
            for($i = 0; $i < $count; ++$i) {
                $pid = \Resque::fork();
                if($pid === false || $pid === -1) {
                    $logger->log(\Psr\Log\LogLevel::EMERGENCY, 'Could not fork worker {count}', array('count' => $i));
                    die();
                }
                // Child, start the worker
                else if(!$pid) {
                    $queues = explode(',', $QUEUE);
                    $worker = new \Resque_Worker($queues);
                    $worker->setLogger($logger);
                    $logger->log(\Psr\Log\LogLevel::NOTICE, 'Starting worker {worker}', array('worker' => $worker));
                    $worker->work($interval, $BLOCKING);
                    break;
                }
            }
        }
        // Start a single worker
        else {
            $queues = explode(',', $QUEUE);
            $worker = new \Resque_Worker($queues);
            $worker->setLogger($logger);

            $PIDFILE = getenv('PIDFILE');
            if ($PIDFILE) {
                file_put_contents($PIDFILE, getmypid()) or
                    die('Could not write PID information to ' . $PIDFILE);
            }

            $logger->log(\Psr\Log\LogLevel::NOTICE, 'Starting worker {worker}', array('worker' => $worker));
            $worker->work($interval, $BLOCKING);
        }

        $response = $this->getResponse();
        $response->exitStatus = 200;

        return $response;

    }

}
