<?php

namespace Rogue\Http\Controllers\Three;

use Rogue\Models\Post;
use Illuminate\Http\Request;
use Rogue\Services\Three\PostService;
use Rogue\Http\Requests\Three\PostRequest;
use Rogue\Http\Controllers\Api\ApiController;
use Rogue\Repositories\Three\SignupRepository;
use Illuminate\Auth\Access\AuthorizationException;
use Rogue\Http\Controllers\Traits\FiltersRequests;
use Rogue\Http\Transformers\Three\PostTransformer;

class PostsController extends ApiController
{
    use FiltersRequests;

    /**
     * The post service instance.
     *
     * @var \Rogue\Services\Three\PostService
     */
    protected $posts;

    /**
     * The signup repository instance.
     *
     * @var \Rogue\Repositories\Three\SignupRepository
     */
    protected $signups;

    /**
     * @var \Rogue\Http\Transformers\PostTransformer;
     */
    protected $transformer;

    /**
     * Use cursor pagination for these routes.
     *
     * @var bool
     */
    protected $useCursorPagination = true;

    /**
     * Create a controller instance.
     *
     * @param PostService $posts
     * @param SignupRepository $signups
     * @param PostTransformer $transformer
     */
    public function __construct(PostService $posts, SignupRepository $signups, PostTransformer $transformer)
    {
        $this->posts = $posts;
        $this->signups = $signups;
        $this->transformer = $transformer;

        $this->middleware('auth:api', ['only' => ['store', 'update', 'destroy']]);
        $this->middleware('role:admin', ['only' => ['destroy']]);
    }

    /**
     * Returns Posts, filtered by params, if provided.
     * GET /posts
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $query = $this->newQuery(Post::class)
            ->withCount('reactions')
            ->with('tags')
            ->orderBy('created_at', 'desc');

        $filters = $request->query('filter');
        $query = $this->filter($query, $filters, Post::$indexes);

        // If a user made the request, return whether or not they liked each post.
        if (auth()->check()) {
            $query = $query->with(['reactions' => function ($query) {
                $query->where('northstar_id', '=', auth()->id());
            }]);
        }

        // Only allow admins or staff to see un-approved posts from other users.
        $query = $query->whereVisible();

        // If tag param is passed, only return posts that have that tag.
        if (array_has($filters, 'tag')) {
            $query = $query->withTag($filters['tag']);
        }

        return $this->paginatedCollection($query, $request);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  PostRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(PostRequest $request)
    {
        $northstarId = getNorthstarId($request);

        $signup = $this->signups->get($northstarId, $request['campaign_id'], $request['campaign_run_id']);

        if (! $signup) {
            $signup = $this->signups->create($request->all(), $northstarId);
        }

        $post = $this->posts->create($request->all(), $signup->id);

        return $this->item($post, 201, [], null, 'signup');
    }

    /**
     * Returns a specific post.
     * GET /posts/:id
     *
     * @param \Rogue\Models\Post $post
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Post $post)
    {
        // Only allow an admin or the user who owns the post to see their own unapproved posts.
        if ($post->status != 'accepted') {
            if (is_staff_user() || auth()->id() === $post->northstar_id) {
                return $this->item($post);
            } else {
                throw new AuthorizationException('You don\'t have the correct role to view this post!');
            }
        }

        return $this->item($post);
    }

    /**
     * Updates a specific post.
     * PATCH /posts/:id
     *
     * @param PostRequest $request
     * @param \Rogue\Models\Post $post
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Post $post)
    {
        $validatedRequest = $request->validate([
            'caption' => 'nullable|string|max:140',
            'quantity' => 'nullable|integer',
        ]);

        // Only allow an admin or the user who owns the post to update.
        if (token()->role() === 'admin' || auth()->id() === $post->northstar_id) {
            $this->posts->update($post, $validatedRequest);

            return $this->item($post);
        }

        throw new AuthorizationException('You don\'t have the correct role to update this post!');
    }

    /**
     * Delete a post.
     * DELETE /posts/:id
     *
     * @param \Rogue\Models\Post $post
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Post $post)
    {
        $this->posts->destroy($post->id);

        return $this->respond('Post deleted.', 200);
    }
}
