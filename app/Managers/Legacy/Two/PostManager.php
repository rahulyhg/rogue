<?php

namespace Rogue\Managers\Legacy\Two;

use Rogue\Models\Post;
use Rogue\Jobs\SendPostToQuasar;
use Rogue\Jobs\SendSignupToQuasar;
use Rogue\Jobs\SendPostToCustomerIo;
use Rogue\Jobs\SendDeletedPostToQuasar;
use Rogue\Jobs\SendReviewedPostToCustomerIo;
use Rogue\Repositories\Legacy\Two\PostRepository;

class PostManager
{
    /*
     * PostRepository Instance
     *
     * @var Rogue\Repositories\Legacy\Two\PostRepository;
     */
    protected $repository;

    /**
     * Constructor
     *
     * @param PostRepository $posts
     * @param Blink $blink
     */
    public function __construct(PostRepository $posts)
    {
        $this->repository = $posts;
    }

    /**
     * Handles all business logic around creating posts.
     *
     * @param array $data
     * @param int $signupId
     * @return \Rogue\Models\Post
     */
    public function create($data, $signupId)
    {
        $post = $this->repository->create($data, $signupId);

        // Send to Blink unless 'dont_send_to_blink' is TRUE
        $should_send_to_blink = ! (array_key_exists('dont_send_to_blink', $data) && $data['dont_send_to_blink']);

        // Save the new post in Customer.io, via Blink.
        if (config('features.blink') && $should_send_to_blink) {
            SendPostToCustomerIo::dispatch($post);
        }

        // Dispatch jobs to send post and signup to Quasar
        SendPostToQuasar::dispatch($post);
        SendSignupToQuasar::dispatch($post->signup);

        // Log that a post was created.
        info('post_created', ['id' => $post->id, 'signup_id' => $post->signup_id, 'post_created_source' => $post->source]);

        return $post;
    }

    /**
     * Handles all business logic around reviewing posts.
     *
     * @param array $data
     * @param int $signupId
     * @return \Rogue\Models\Post
     */
    public function review($data)
    {
        $reviewedPost = $this->repository->reviews($data);

        SendPostToQuasar::dispatch($reviewedPost);
        SendReviewedPostToCustomerIo::dispatch($reviewedPost);

        // Log that a post was reviewed.
        info('post_reviewed', [
            'id' => $reviewedPost->id,
            'admin_northstar_id' => $data['admin_northstar_id'],
            'status' => $reviewedPost->status,
        ]);

        return $reviewedPost;
    }

    /**
     * Handles all business logic around updating posts.
     *
     * @param \Rogue\Models\Signup $signup
     * @param array $data
     * @return \Rogue\Models\Post|\Rogue\Models\Signup
     */
    public function update($signup, $data)
    {
        $postOrSignup = $this->repository->update($signup, $data);

        // Send to Blink unless 'dont_send_to_blink' is TRUE
        $should_send_to_blink = ! (array_key_exists('dont_send_to_blink', $data) && $data['dont_send_to_blink']);

        // Save the new post in Customer.io, via Blink.
        if (config('features.blink') && $postOrSignup instanceof Post && $should_send_to_blink) {
            SendPostToCustomerIo::dispatch($postOrSignup);

            // Log that a post was created.
            info('post_created', ['id' => $postOrSignup->id, 'signup_id' => $postOrSignup->signup_id]);
        }

        // Dispatch job to send Post (or Post and Signup) to Quasar
        if ($postOrSignup instanceof Post) {
            SendPostToQuasar::dispatch($postOrSignup);

            SendSignupToQuasar::dispatch($postOrSignup->signup);
        } elseif ($postOrSignup instanceof Signup) {
            SendSignupToQuasar::dispatch($postOrSignup);
        }

        return $postOrSignup;
    }

    /**
     * Handle all business logic around deleting a post.
     *
     * @param int $postId
     * @return bool
     */
    public function destroy($postId)
    {
        info('post_deleted', [
            'id' => $postId,
        ]);

        $trashed = $this->repository->destroy($postId);

        // Dispatch job to send post to Quasar
        SendDeletedPostToQuasar::dispatch($postId);

        return $trashed;
    }
}
