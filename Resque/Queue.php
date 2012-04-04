<?php
namespace PHPResqueBundle\Resque;

class Queue {

    private $control;

    public function __construct($control) 
    {
        $this->control = $control;
    }

    public function add($job_name, $queue_name, $args = array()) 
    {
        $namespace = null;
        if (strpos($queue_name, ':') !== false) {
            list($namespace, $queue_name) = explode(':', $queue_name);
        }
        
        $this->control->setup($namespace);

        try {
            $class = new \ReflectionClass($job_name);
            $jobId = \Resque::enqueue($queue_name, $class->getName(), $args, true);

            return $jobId;
        } catch (\ReflectionException $rfe) {
            throw new \RuntimeException($rfe->getMessage());
        }
    }
}
