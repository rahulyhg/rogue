<?php

namespace Rogue\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SendDeletedSignupToQuasar implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The signup to send to Quasar via Blink.
     *
     * @var int
     */
    protected $signupId;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($signupId)
    {
        $this->signupId = $signupId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $payload = [
            'id' => $this->signupId,
            'deleted_at' => Carbon::now()->toIso8601String(),
            'meta' => [
                'message_source' => 'rogue',
                'type' => 'signup',
            ],
        ];

        // Send to Quasar
        $shouldSendToQuasar = config('features.pushToQuasar');
        if ($shouldSendToQuasar) {
            gateway('blink')->post('v1/events/quasar-relay', $payload);
        }

        // Log
        $verb = $shouldSendToQuasar ? 'sent' : 'would have been sent';
        info('Deleted signup ' . $this->signupId . ' ' . $verb . ' sent to Quasar');
    }
}
