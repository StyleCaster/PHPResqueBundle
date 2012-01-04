<?php
namespace PHPResqueBundle\Resque;

class Queue {

    private $backend = '';

    public function __construct($backend) {
        $this->backend = $backend;
    }

    public static function add($job_name, $queue_name, $args = array()) {
        \Resque::setBackend($this->backend);

        if (strpos($queue_name, ':') !== false) {
            list($namespace, $queue_name) = explode(':', $queue_name);
            \Resque_Redis::prefix($namespace);
        }

        try {
            $class = new \ReflectionClass($job_name);
            $jobId = \Resque::enqueue($queue_name, $class->getName(), $args, true);

            return $jobId;
        } catch (\ReflectionException $rfe) {
            throw new \RuntimeException($rfe->getMessage());
        }
    }
}
