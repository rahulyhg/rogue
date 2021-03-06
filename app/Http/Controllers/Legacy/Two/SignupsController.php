<?php

namespace Rogue\Http\Controllers\Legacy\Two;

use Rogue\Managers\Legacy\Two\PostManager;
use Rogue\Managers\Legacy\Two\SignupManager;
use Rogue\Http\Requests\Legacy\Two\SignupRequest;
use Rogue\Http\Transformers\Legacy\Two\SignupTransformer;

class SignupsController extends ApiController
{
    /**
     * @var \League\Fractal\TransformerAbstract;
     */
    protected $transformer;

    /**
     * The signup manager instance.
     *
     * @var Rogue\Managers\Legacy\Two\SignupManager
     */
    protected $signups;

    /**
     * The photo manager instance.
     *
     * @var Rogue\Managers\Legacy\Two\PostManager
     */
    protected $posts;

    /**
     * Create a controller instance.
     *
     * @param  PostContract  $posts
     * @return void
     */
    public function __construct(SignupManager $signups, PostManager $posts)
    {
        $this->signups = $signups;
        $this->posts = $posts;

        $this->transformer = new SignupTransformer;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(SignupRequest $request)
    {
        // Check to see if the signup exists before creating one.
        $signup = $this->signups->get($request['northstar_id'], $request['campaign_id']);

        $code = $signup ? 200 : 201;

        if (! $signup) {
            $signup = $this->signups->create($request->all());
        }

        // check to see if there is a reportback too aka we are migratin'
        if ($request->has('photo')) {
            // create the photo and tie it to this signup
            foreach ($request->photo as $photo) {
                $this->posts->create($photo, $signup->id);
            }
        }

        return $this->item($signup, $code);
    }
}
