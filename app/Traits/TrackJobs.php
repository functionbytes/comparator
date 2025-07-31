<?php

namespace App\Traits;

use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Support\Facades\Bus;
use App\Models\JobMonitor;
use Illuminate\Bus\Batch;
use Throwable;
use Exception;
use Illuminate\Support\Facades\DB;

trait TrackJobs
{

    public function jobMonitors()
    {
        return $this->hasMany('App\Models\JobMonitor', 'subject_id')->where('subject_name', self::class);
    }

    // DO NOT USE DB TRANSACTION
    // OTHERWISE BATCH_ID OR JOB_ID MAY NOT BE AVAILABLE
    public function dispatchWithMonitor($job)
    {
        $jobType = get_class($job);
        $monitor = JobMonitor::makeInstance($this, $jobType); // QUEUED status

        // actually save
        $monitor->save();
        $job->setMonitor($monitor);

        $events = [
            $job->eventAfterDispatched
        ];

        $job->eventAfterDispatched = null;

        $dispatchedJobId = app(Dispatcher::class)->dispatch($job);

        $monitor->job_id = $dispatchedJobId;
        $monitor->save();

        foreach ($events as $closure) {
            if (!is_null($closure)) {
                $closure($job, $monitor);
            }
        }

        // Return
        return $monitor;
    }

    public function dispatchWithBatchMonitor($job, $thenCallback, $catchCallback, $finallyCallback)
    {

        if (!property_exists($job, 'monitor')) {
            throw new Exception(sprintf('Job class `%s` should use `Trackable` trait in order to use $eventAfterDispatched callback', get_class($job)));
        }

        $monitor = JobMonitor::makeInstance($this, get_class($job));
        $monitor->save();

        $job->setMonitor($monitor);

        $events = [
            'afterDispatched' => $job->eventAfterDispatched,
            'afterFinished' => $job->eventAfterFinished,
        ];

        $job->eventAfterDispatched = null;
        $job->eventAfterFinished = null;

        $batch = Bus::batch($job)->then(function (Batch $batch) use ($monitor, $thenCallback) {
            // Finish successfully
            $monitor->setDone();

            if (!is_null($thenCallback)) {
                $thenCallback($batch);
            }
        })->catch(function (Batch $batch, \Throwable $e) use ($monitor, $catchCallback) {
            // Failed and finish
            $monitor->setFailed($e);

            if (!is_null($catchCallback)) {
                $catchCallback($batch, $e);
            }
        })->finally(function (Batch $batch) use ($monitor, $finallyCallback, $events, $job) {
            if (!is_null($finallyCallback)) {
                $finallyCallback($batch);
            }

            // Execute job's callback
            if (array_key_exists('afterFinished', $events)) {
                $closure = $events['afterFinished'];
                if (!is_null($closure)) {
                    $closure($job, $monitor);
                }
            }
        })->onQueue('batch')->dispatch();

        $monitor->batch_id = $batch->id;
        $monitor->save();

        // Execute job's callback
        if (array_key_exists('afterDispatched', $events)) {
            $closure = $events['afterDispatched'];
            if (!is_null($closure)) {
                $closure($job, $monitor);
            }
        }

        // Return
        return $monitor;
    }

    public function cancelAndDeleteJobs($jobType = null)
    {
        $query = $this->jobMonitors();

        if (!is_null($jobType)) {
            $query = $query->byJobType($jobType);
        }

        foreach ($query->get() as $job) {
            $job->cancel();
        }
    }
}
