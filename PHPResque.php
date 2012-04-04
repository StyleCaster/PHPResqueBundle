<?php
namespace PHPResqueBundle;

class PHPResque
{
    private $queue = '*';
    private $logging = 'normal';
    private $checker_interval = 5;
    private $fork_count = 1;
    private $backend = '';
    private $password = '';

    public function __construct($backend, $password) {
        $this->backend = $backend;
        $this->password = $password;
    }

    public function defineQueue($name) {
        $this->queue = $name;
    }

    public function verbose($mode) {
        $this->logging = $mode;
    }

    public function setInterval($interval) {
        $this->checker_interval = (int)$interval;
    }

    public function forkInstances($count) {
        settype($count, 'int');

        if ($count > 1) {
            if (function_exists('pcntl_fork')) {
                $this->fork_count = $count;
            } else {
                fwrite(STDOUT, "*** Fork could not initialized. PHP function pcntl_fork() does NOT exists \n");
                $this->fork_count = 1;
            }
        } else {
            $this->fork_count = 1;
        }
    }

    public function getForkInstances() {
        return $this->fork_count;
    }

    private function loglevel() {
        switch ($this->logging) {
            case 'verbose' :
                return \Resque_Worker::LOG_VERBOSE;
            case 'normal' :
                return \Resque_Worker::LOG_NORMAL;
            default :
                return \Resque_Worker::LOG_NONE;
        }
    }

    private function work() {
        $worker = new \Resque_Worker(explode(',', $this->queue));
        $worker->logLevel = $this->loglevel();
        $worker->work($this->checker_interval);
        fwrite(STDOUT, '*** Starting worker ' . $worker . "\n");
    }

    public function daemon() {
        $namespace = null;

        if (strpos($this->queue, ':') !== false) {
            list($namespace, $queue) = explode(':', $this->queue);
            $this->queue = $queue;
        }

        $this->setup($namespace);

        if ($this->getForkInstances() > 1) {
            for ($i = 0; $i < $this->getForkInstances(); ++$i) {
                $pid = pcntl_fork();

                if ($pid == -1) {
                    throw new \RuntimeException("Could not fork worker {$i}");
                }

                $this->work();
                break;
            }
        } else {
            $this->work();
        }
    }
    
    public function setup($namespace = null)
    { 
        \Resque::setBackend($this->backend);

        if (isset($this->password)) {
            \Resque::redis()->auth($this->password);
        }
        
        if (isset($namespace)) {
            \Resque_Redis::prefix($namespace);
        }
    }
}