<?php

namespace Rogue\Console\Commands;

use Rogue\Models\Signup;
use Illuminate\Console\Command;

class PostQuantity extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rogue:postquantity {start=1}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add the quantity from the corresponding signup onto the corresponding post.';

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
        // Signup that we should start with
        $start = $this->argument('start');

        // Create the progress bar
        $bar = $this->output->createProgressBar(Signup::where('id', '>=', $start)->count());

        // Get all signups starting from $start in order of ID
        Signup::where('id', '>=', $start)->orderBy('id')->with('posts')->chunk(100, function ($signups) use ($bar) {
            foreach ($signups as $signup) {
                // Get the posts for the signup
                $posts = $signup->posts;

                // If no posts, move on
                if ($posts->isEmpty()) {
                    info('rogue:postquantity: No posts for signup ' . $signup->id);
                    $bar->advance();
                    continue;
                }

                // Sum quant of posts
                info('rogue:postquantity: Updating posts for signup ' . $signup->id);
                $postQuantityTotal = $posts->sum('quantity');

                // If >0, see if any posts have null quant
                if ($postQuantityTotal) {
                    $postQuantities = $posts->pluck('quantity')->toArray();

                    // If none have null quant, continue we are done here
                    if (! in_array(null, $postQuantities, true)) {
                        continue;
                    }

                    // If some have null quant we need to fill them in
                    $missingQuantity = $signup->getQuantity() - $postQuantityTotal;
                    $this->putQuantityOnPosts($posts, $missingQuantity);

                    // Advance the progress bar
                    $bar->advance();

                    // Move on to the next signup
                    continue;
                }

                // If no posts have a quantity yet
                $quantity = $signup->getQuantity();
                $this->putQuantityOnPosts($posts, $quantity);

                // Advance the progress bar
                $bar->advance();
            }
        });
        // We did it!
        $bar->finish();
        info('rogue:postquantity: ALL DONE');
    }

    public function putQuantityOnPosts($posts, $quantity)
    {
        // Put all the quantity on the most recent accepted post (based on creation)
        $acceptedPosts = $posts->where('status', 'accepted')->sortByDesc('created_at');
        if ($acceptedPosts->isNotEmpty()) {
            $mostRecentAcceptedPost = $acceptedPosts->first();
            $mostRecentAcceptedPost->quantity = $quantity;
            $mostRecentAcceptedPost->save();
            info('rogue:postquantity: Put quantity ' . $quantity . ' on post ' . $mostRecentAcceptedPost->id);
        }
        // If no accepted posts, put quantity on most recent post (based on creation)
        else {
            $mostRecentPost = $posts->sortByDesc('created_at')->first();
            $mostRecentPost->quantity = $quantity;
            $mostRecentPost->save();
            info('rogue:postquantity: Put quantity ' . $quantity . ' on post ' . $mostRecentPost->id);
        }
        // Put quantity of 0 on all other posts under this signup
        foreach ($posts as $post) {
            if (is_null($post->quantity)) {
                $post->quantity = 0;
                $post->save();
                info('rogue:postquantity: Put quantity 0 on post ' . $post->id);
            }
        }
    }
}