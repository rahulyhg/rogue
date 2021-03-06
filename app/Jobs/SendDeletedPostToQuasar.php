<?php

namespace Rogue\Jobs;

use Carbon\Carbon;
use Rogue\Models\Post;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SendDeletedPostToQuasar implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The post to send to Quasar via Blink.
     *
     * @var int
     */
    protected $postId;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($postId)
    {
        $this->postId = $postId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $payload = [
            'id' => $this->postId,
            'deleted_at' => Carbon::now()->toIso8601String(),
            'meta' => [
                'message_source' => 'rogue',
                'type' => 'post',
            ],
        ];

        // Send to Quasar
        $shouldSendToQuasar = config('features.pushToQuasar');
        if ($shouldSendToQuasar) {
            gateway('blink')->post('v1/events/quasar-relay', $payload);
        }

        // Log
        $verb = $shouldSendToQuasar ? 'sent' : 'would have been sent';
        info('Deleted post ' . $this->postId . ' ' . $verb . ' sent to Quasar');
    }
}
