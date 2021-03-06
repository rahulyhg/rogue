<?php

namespace Rogue\Http\Controllers;

use Rogue\Models\Signup;
use Rogue\Models\Campaign;
use Illuminate\Http\Request;
use Rogue\Managers\SignupManager;
use Rogue\Http\Transformers\SignupTransformer;
use Illuminate\Auth\Access\AuthorizationException;
use Rogue\Http\Controllers\Traits\TransformsRequests;

class SignupsController extends ApiController
{
    use TransformsRequests;

    /**
     * @var Rogue\Http\Transformers\SignupTransformer;
     */
    protected $transformer;

    /**
     * The signup manager instance.
     *
     * @var \Rogue\Manager\SignupManager
     */
    protected $signups;

    /**
     * Use cursor pagination for these routes.
     *
     * @var bool
     */
    protected $useCursorPagination = true;

    /**
     * Create a controller instance.
     *
     * @param SignupManager $signups
     */
    public function __construct(SignupManager $signups)
    {
        $this->signups = $signups;
        $this->transformer = new SignupTransformer;

        $this->middleware('scopes:activity');
        $this->middleware('auth:api', ['only' => ['store', 'update', 'destroy']]);
        $this->middleware('role:admin,staff', ['only' => ['destroy']]);
        $this->middleware('scopes:write', ['only' => ['store', 'update', 'destroy']]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'campaign_id' => 'required_without:action_id|integer',
            'action_id' => 'required_without:campaign_id|integer',
            'why_participated' => 'string',
        ]);

        $northstarId = getNorthstarId($request);

        // Get the campaign id from the request by campaign_id or action_id.
        $campaignId = $request['campaign_id'] ? $request['campaign_id'] : Campaign::fromActionId($request['action_id'])->id;

        // Check to see if the signup exists before creating one.
        $signup = $this->signups->get($northstarId, $campaignId);

        $code = $signup ? 200 : 201;

        if (! $signup) {
            $signup = $this->signups->create($request->all(), $northstarId, $campaignId);
        }

        return $this->item($signup, $code);
    }

    /**
     * Returns signups.
     * GET /signups
     *
     * @param \Illuminate\Http\Request $request
     * @return Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = $this->newQuery(Signup::class);

        $filters = $request->query('filter');
        $query = $this->filter($query, $filters, Signup::$indexes);

        // Only allow an admin or the user who owns the signup to see the signup's unapproved posts.
        if (starts_with($request->query('include'), 'posts')) {
            $types = (new \League\Fractal\Manager)
                ->parseIncludes($request->query('include'))
                ->getIncludeParams('posts');

            $types = $types ? $types->get('type') : null;

            $query = $query->withVisiblePosts($types);
        }

        $orderBy = $request->query('orderBy');

        if ($orderBy) {
            list($column, $direction) = explode(',', $orderBy, 2);

            if (in_array($column, Signup::$indexes)) {
                $query = $query->orderBy($column, $direction);
            }
        }

        return $this->paginatedCollection($query, $request);
    }

    /**
     * Returns a specific signup.
     * GET /signups/:id
     *
     * @param \Illuminate\Http\Request $request
     * @param \Rogue\Models\Signup $signup
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, Signup $signup)
    {
        // Only allow an admin or the user who owns the signup to see the signup's unapproved posts.
        if (starts_with($request->query('include'), 'posts')) {
            $types = (new \League\Fractal\Manager)
                ->parseIncludes($request->query('include'))
                ->getIncludeParams('posts');

            $types = $types ? $types->get('type') : null;

            $signup->load(['visiblePosts' => function ($query) use ($types) {
                if ($types) {
                    $query->whereIn('type', $types);
                }
            }]);
        }

        return $this->item($signup, 200, [], $this->transformer, $request->query('include'));
    }

    /**
     * Updates a specific signup.
     * PATCH /signups/:id
     *
     * @param \Illuminate\Http\Request $request
     * @param \Rogue\Models\Signup $signup
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Signup $signup)
    {
        $validatedRequest = $this->validate($request, [
            'why_participated' => 'required',
        ]);

        // Only allow an admin or the user who owns the signup to update.
        if (token()->role() === 'admin' || auth()->id() === $signup->northstar_id) {
            // why_participated is the only thing that can be changed
            $this->signups->update($signup, $validatedRequest);

            return $this->item($signup);
        }

        throw new AuthorizationException('You don\'t have the correct role to update this signup!');
    }

    /**
     * Delete a signup.
     * DELETE /signups/:id
     *
     * @param \Rogue\Models\Signup $signup
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Signup $signup)
    {
        $trashed = $this->signups->destroy($signup->id);

        if ($trashed) {
            return $this->respond('Signup deleted.', 200);
        }

        return response()->json(['code' => 500, 'message' => 'There was an error deleting the post']);
    }
}
