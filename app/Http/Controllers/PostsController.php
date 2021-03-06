<?php

namespace Rogue\Http\Controllers;

use Rogue\Models\Post;
use Rogue\Models\Campaign;
use Illuminate\Http\Request;
use Rogue\Managers\PostManager;
use Rogue\Managers\SignupManager;
use Illuminate\Support\Facades\Gate;
use Rogue\Http\Requests\PostRequest;
use Rogue\Http\Transformers\PostTransformer;
use Rogue\Http\Controllers\Traits\FiltersRequests;

class PostsController extends ApiController
{
    use FiltersRequests;

    /**
     * The post manager instance.
     *
     * @var \Rogue\Managers\PostManager
     */
    protected $posts;

    /**
     * The SignupManager instance.
     *
     * @var \Rogue\Managers\SignupManager
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
     * @param PostManager $posts
     * @param SignupManager $signups
     * @param PostTransformer $transformer
     */
    public function __construct(PostManager $posts, SignupManager $signups, PostTransformer $transformer)
    {
        $this->posts = $posts;
        $this->signups = $signups;
        $this->transformer = $transformer;

        $this->middleware('scopes:activity');
        $this->middleware('auth:api', ['only' => ['store', 'update', 'destroy']]);
        $this->middleware('role:admin,staff', ['only' => ['destroy']]);
        $this->middleware('scopes:write', ['only' => ['store', 'update', 'destroy']]);
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
            ->orderBy('created_at', 'desc');

        if (has_include($request, 'signup')) {
            // Eagerly load the `signup` relationship.
            $query->with('signup');
        }

        $filters = $request->query('filter');
        $query = $this->filter($query, $filters, Post::$indexes);

        // Only allow admins, staff, or owner to see un-approved posts from other users.
        $query = $query->whereVisible();

        // Only return posts tagged "Hide In Gallery" if staff user or if is owner of the post.
        $query = $query->withHiddenPosts();

        // If tag param is passed, only return posts that have that tag.
        if (array_has($filters, 'tag')) {
            $query = $query->withTag($filters['tag']);
        }

        // If the northstar_id param is passed, only allow admins, staff, or owner to see anonymous posts.
        if (array_has($filters, 'northstar_id')) {
            $query = $query->withoutAnonymousPosts();
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

        // Get the campaign id from the request by campaign_id or action_id.
        $campaignId = $request['campaign_id'] ? $request['campaign_id'] : Campaign::fromActionId($request['action_id'])->id;

        $signup = $this->signups->get($northstarId, $campaignId);

        if (! $signup) {
            $signup = $this->signups->create($request->all(), $northstarId, $campaignId);
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
        $this->authorize('show', $post);

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
    public function update(PostRequest $request, Post $post)
    {
        // Only allow an admin/staff or the user who owns the post to update.
        $this->authorize('update', $post);

        // But don't allow user's to review their own posts.
        if (! Gate::allows('review', $post)) {
            unset($request['status']);
        }

        $this->posts->update($post, $request->validated());

        return $this->item($post);
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
