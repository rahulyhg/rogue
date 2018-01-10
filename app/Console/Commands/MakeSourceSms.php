<?php

namespace Rogue\Console\Commands;

use Rogue\Models\Post;
use Rogue\Models\Signup;
use Illuminate\Console\Command;

class MakeSourceSms extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rogue:smssource';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Change all "sms-mobilecommons" source value to "sms" in the signups and posts tables';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // Start updating signups
        info('rogue:smssource: Starting to update signups!');

        // Get all signups that have "sms-mobilecommons" set as their source and update to "sms"
        Signup::where('source', 'sms-mobilecommons')->chunkById(100, function ($signups) {
            foreach ($signups as $signup) {
                info('rogue:smssource: changing source to sms for signup ' . $signup->id);
                $signup->source = 'sms';
                $signup->save();
            }
        });

        // Log that updating signups are finished
        info('rogue:smssource: Finished updating signups!');

        // Start updating posts
        info('rogue:smssource: Starting to update posts!');

        // Get all posts that have "sms-mobilecommons" set as their source and update to "sms"
        Post::where('source', 'sms-mobilecommons')->chunkById(100, function ($posts) {
            foreach ($posts as $post) {
                info('rogue:smssource: changing source to sms for post ' . $post->id);
                $post->source = 'sms';
                $post->save();
            }
        });

        // Lost that updating posts are finished
        info('rogue:smssource: Finished updating posts!');
    }
}
