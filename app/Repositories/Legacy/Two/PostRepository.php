<?php

namespace Rogue\Repositories\Legacy\Two;

use Rogue\Models\Post;
use Rogue\Services\AWS;
use Rogue\Models\Review;
use Rogue\Models\Signup;
use Rogue\Services\Registrar;
use Intervention\Image\Facades\Image;

class PostRepository
{
    /**
     * AWS service class instance.
     *
     * @var \Rogue\Services\AWS
     */
    protected $aws;

    /**
     * The user repository.
     *
     * @var \Rogue\Services\Registrar
     */
    protected $registrar;

    /**
     * Array of properties needed for cropping and rotating.
     *
     * @var array
     */
    protected $cropProperties = ['crop_x', 'crop_y', 'crop_width', 'crop_height', 'crop_rotate'];

    /**
     * Create a PostRepository.
     *
     * @param AWS $aws
     * @param Registrar $registrar
     */
    public function __construct(AWS $aws, Registrar $registrar)
    {
        $this->aws = $aws;
        $this->registrar = $registrar;
    }

    /**
     * Find a post by post_id and return associated signup and tags.
     *
     * @param int $id
     * @return \Rogue\Models\Post
     */
    public function find($id)
    {
        return Post::with('signup', 'tags')->findOrFail($id);
    }

    /**
     * Create a Post.
     *
     * @param  array $data
     * @param  int $signupId
     * @return \Rogue\Models\Post|null
     */
    public function create(array $data, $signupId)
    {
        if (isset($data['file'])) {
            // Auto-orient the photo by default based on exif data.
            $image = Image::make($data['file']);

            $fileUrl = $this->aws->storeImage((string) $image->encode('data-url'), $signupId);
        } else {
            $fileUrl = null;
        }

        $signup = Signup::withCount('posts')->find($signupId);

        // Create a post.
        $post = new Post([
            'signup_id' => $signup->id,
            'northstar_id' => $data['northstar_id'],
            'campaign_id' => $signup->campaign_id,
            'url' => $fileUrl,
            'text' => $data['caption'],
            'status' => isset($data['status']) ? $data['status'] : 'pending',
            'source' => isset($data['source']) ? $data['source'] : null,
            'source_details' => isset($data['source_details']) ? $data['source_details'] : null,
            'type' => isset($data['type']) ? $data['type'] : 'photo',
            'action' => isset($data['action']) ? $data['action'] : 'default',
        ]);

        // If we are supporting quantity on posts and
        // we recieved a quantity in the request.
        // Then, store correct quantity on the Post.
        if (config('features.v3QuantitySupport') && isset($data['quantity'])) {
            $quantityDiff = $data['quantity'] - $signup->quantity;

            // If the quantity difference is negative than we recieved an incremental submission
            // and should just add that to the post.
            // If the quantity difference equals zero and this is the first post,
            // the post and signup were created at the same time -
            // store this quantity on both the post and signup.
            if ($quantityDiff < 0 || ($quantityDiff === 0 && $signup->posts_count === 0)) {
                $quantityDiff = $data['quantity'];
            } elseif ($quantityDiff === 0 && $signup->posts_count > 0) {
                // If the quantity difference equals zero, and this is not the first post,
                // then we can assume there is no difference in quantity and store it as 0 on the post.
                $quantityDiff = 0;
            }

            $post->quantity = $quantityDiff;
        }

        // @TODO: This can be removed after the migration
        // Let Laravel take care of the timestamps unless they are specified in the request
        if (isset($data['created_at'])) {
            $post->created_at = $data['created_at'];
            $post->updated_at = isset($data['updated_at']) ? $data['updated_at'] : $data['created_at'];
            $post->save(['timestamps' => false]);

            $post->events->first()->created_at = $data['created_at'];
            $post->events->first()->updated_at = $data['created_at'];
            $post->events->first()->save(['timestamps' => false]);
        } else {
            $post->save();
        }

        if (config('features.v3QuantitySupport') && isset($data['quantity'])) {
            // Update signup quantity. If supporting quantity on the post, we will get a summation of posts across the signup. Otherwise, we will just get the current signup quantity.
            $signup->quantity = $signup->getQuantity();
            $signup->save();
        }

        // Edit the image if there is one
        if (isset($data['file'])) {
            $this->crop($data, $post->id);
        }

        return $post;
    }

    /**
     * Update an existing Post and Signup.
     *
     * @param \Rogue\Models\Signup $signup
     * @param array $data
     *
     * @return Signup|Post
     */
    public function update($signup, $data)
    {
        if (array_key_exists('updated_at', $data)) {
            // Should only update why_participated, and timestamps on the signup
            $signupFields = [
                'why_participated' => isset($data['why_participated']) ? $data['why_participated'] : null,
                'updated_at' => $data['updated_at'],
                'created_at' => array_key_exists('created_at', $data) ? $data['created_at'] : null,
            ];

            // Only update if the key is set (is not null).
            $nonNullArrayKeys = array_filter($signupFields);
            $arrayKeysToUpdate = array_keys($nonNullArrayKeys);

            $signup->fill(array_only($data, $arrayKeysToUpdate));
            $signup->save(['timestamps' => false]);

            $event = $signup->events->last();
            $event->created_at = $data['updated_at'];
            $event->updated_at = $data['updated_at'];
            $event->save(['timestamps' => false]);
        } else {
            // Should only update why_participated on the signup
            $signupFields = [
                'why_participated' => isset($data['why_participated']) ? $data['why_participated'] : null,
            ];

            // Only update if the key is set (is not null).
            $nonNullArrayKeys = array_filter($signupFields);
            $arrayKeysToUpdate = array_keys($nonNullArrayKeys);

            $signup->fill(array_only($data, $arrayKeysToUpdate));

            // Triggers model event that logs the updated signup in the events table.
            $signup->save();
        }

        // If we are not storing quantity on the post then we still need to put it on the signup.
        if (! config('features.v3QuantitySupport') && isset($data['quantity'])) {
            $signup->quantity = $data['quantity'];
            $signup->save();
        }

        // If there is a file, create a new post.
        if (array_key_exists('file', $data)) {
            return $this->create($data, $signup->id);
        }

        return $signup;
    }

    /**
     * Delete a post and remove the file from s3.
     *
     * @param int $postId
     * @return $post;
     */
    public function destroy($postId)
    {
        $post = Post::findOrFail($postId);

        // Delete the image file from AWS.
        $this->aws->deleteImage($post->url);

        // Set the url of the post to null.
        $post->url = null;
        $post->save();

        // Soft delete the post.
        $post->delete();

        return $post->trashed();
    }

    /**
     * Updates a post's status after being reviewed.
     *
     * @param array $data
     *
     * @return Post
     */
    public function reviews($data)
    {
        $post = Post::where(['id' => $data['post_id']])->first();

        // Create the Review.
        $review = Review::create([
            'signup_id' => $post->signup_id,
            'northstar_id' => $post->northstar_id,
            'admin_northstar_id' => $data['admin_northstar_id'],
            'status' => $data['status'],
            'old_status' => $post->status,
            'comment' => isset($data['comment']) ? $data['comment'] : null,
            'post_id' => $post->id,
        ]);

        // Update the status on the Post.
        $post->status = $data['status'];
        $post->save();

        return $post;
    }

    /**
     * Updates a post's tags when added or deleted.
     *
     * @param object $post
     * @param string $tag
     *
     * @return
     */
    public function tag(Post $post, $tag)
    {
        // If the post already has the tag, soft delete. Otherwise, add the tag to the post.
        if ($post->tagNames()->contains($tag)) {
            $post->untag($tag);
        } else {
            $post->tag($tag);
        }

        // Return the post object including the tags that are related to it.
        return Post::with('signup', 'tags')->findOrFail($post->id);
    }

    /**
     * Crop an image
     *
     * @TODO - remove when glide is permanent.
     *
     * @param  int $signupId
     * @return url|null
     */
    protected function crop($data, $postId)
    {
        $editedImage = Image::make($data['file']);

        // use default crop (400x400)
        $editedImage = $editedImage->fit(400, 400)->encode('jpg', 75);

        return $this->aws->storeImageData((string) $editedImage, 'edited_' . $postId);
    }
}
