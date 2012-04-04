<?php
namespace PHPResqueBundle\Resque;

class Status {

    private $control;

    public function __construct($control) 
    {
        $this->control = $control;
    }

    public static function check($job_id, $namespace) 
    {
        $this->control->setup($namespace);

        $status = new \Resque_Job_Status($job_id);
        if (!$status->isTracking()) {
            die("Resque is not tracking the status of this job.\n");
        }

        $class = new \ReflectionObject($status);

        foreach ($class->getConstants() as $constant_name => $constant_value) {
            if ($constant_value == $status->get()) {
                break;
            }
        }

        return 'Job status in queue is ' . $status->get() . " [$constant_name]";
    }

    public static function update($status, $to_job_id, $namespace) 
    {
        $this->control->setup($namespace);

        $job = new \Resque_Job_Status($to_job_id);

        if (!$job->get()) {
            throw new \RuntimeException("Job {$to_job_id} was not found");
        }

        $class = new \ReflectionObject($job);

        foreach ($class->getConstants() as $constant_value) {
            if ($constant_value == $status) {
                $job->update($status);
                return true;
            }
        }

        return false;
    }
}