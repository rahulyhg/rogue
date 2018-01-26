<?php

namespace Rogue\Http\Controllers\Three;

use Illuminate\Http\Request;
use Rogue\Repositories\Three\PostRepository;
use Rogue\Http\Transformers\Three\PostTransformer;
use Rogue\Http\Controllers\Api\ApiController;
use Illuminate\Auth\Access\AuthorizationException;

class TagsController extends ApiController
{
    /**
     * The post service instance.
     *
     * @var Rogue\Repositories\Three\PostRepository
     */
    protected $post;

    /**
     * @var \League\Fractal\TransformerAbstract;
     */
    protected $transformer;

    /**
     * Create a controller instance.
     *
     * @param  PostContract $posts
     * @return void
     */
    public function __construct(PostRepository $post)
    {
        $this->post = $post;
        $this->transformer = new PostTransformer;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'post_id' => 'required',
            'tag_name' => 'required|string',
        ]);

        // Only allow an admin to review the post.
        if (token()->role() === 'admin') {
            $post = $this->post->find($request->post_id);

            $taggedPost = $this->post->tag($post, $request->tag_name);

            return $this->item($taggedPost);
        }

        throw new AuthorizationException('You don\'t have the correct role to tag this post!');
    }
}
