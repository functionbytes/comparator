<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Queue;
use Illuminate\Contracts\Queue\ShouldQueue;

class JobServiceProvider extends ServiceProvider
{
    public function boot(): void
    {


        Queue::before(function (JobProcessing $event) {
            $job = $this->getJobObject($event);
            if ($job && property_exists($job, 'monitor')) {
                $job->monitor->setRunning();
            }
        });

        // 'after' event for standalone jobs only (not batch jobs)
        Queue::after(function (JobProcessed $event) {
            $job = $this->getJobObject($event);
            if ($job && property_exists($job, 'monitor') && is_null($job->monitor->batch_id)) {
                $job->monitor->setDone();
            }
        });

        // Use failing instead of catching for compatibility with DatabaseQueue
        Queue::failing(function (JobFailed $event) {
            $job = $this->getJobObject($event);
            if ($job && property_exists($job, 'monitor') && is_null($job->monitor->batch_id)) {
                $job->monitor->setFailed($event->exception);
            }
        });
    }

    public function register(): void
    {
    }

    protected function getJobObject($event)
    {
        try {
            $payload = $event->job->payload();

            // Laravel serializa el Job en 'command'
            if (isset($payload['data']['command'])) {
                return unserialize($payload['data']['command']);
            }

            return null;
        } catch (\Throwable $e) {
            \Log::error('Error getting job object: ' . $e->getMessage());
            return null;
        }
    }


}
