<?php

namespace Rogue\Http\Controllers\Legacy\Web;

use Rogue\PostType;
use Rogue\Models\Action;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Rogue\Http\Controllers\Controller;

class ActionsController extends Controller
{
    /**
     * Create a controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:admin,staff', ['only' => ['create', 'store', 'edit', 'update', 'destroy']]);
    }

    /**
     * Create a new action.
     */
    public function create($campaignId)
    {
        return view('actions.create')->with([
            'postTypes' => PostType::all(),
            'campaignId' => (int) $campaignId,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|string',
            'campaign_id' => 'required|integer|exists:campaigns,id',
            'post_type' => 'required|string|in:photo,voter-reg,text,share-social,phone-call',
            'callpower_campaign_id' => 'nullable|required_if:post_type,phone-call|integer|unique:actions',
            'noun' => 'required|string',
            'verb' => 'required|string',
        ]);

        // Checkbox values are only sent from the front end if they are checked.
        // Get checkbox values if sent from the front end or via the API.
        $request['reportback'] = isset($request['reportback']) && $request['reportback'] ? true : false;
        $request['civic_action'] = isset($request['civic_action']) && $request['civic_action'] ? true : false;
        $request['scholarship_entry'] = isset($request['scholarship_entry']) && $request['scholarship_entry'] ? true : false;
        $request['anonymous'] = isset($request['anonymous']) && $request['anonymous'] ? true : false;

        // Check to see if the action exists before creating one.
        $action = Action::where([
            'name' => $request['name'],
            'campaign_id' => $request['campaign_id'],
            'post_type' => $request['post_type'],
        ])->first();

        if (! $action) {
            $action = Action::create($request->all());

            // Log that a action was created.
            info('action_created', ['id' => $action->id]);
        }

        return redirect()->route('campaign-ids.show', $request['campaign_id']);
    }

    /**
     * Edit an existing action.
     */
    public function edit($campaignId, $actionId)
    {
        return view('actions.edit')->with([
            'campaignId' => $campaignId,
            'action' => Action::find($actionId),
            'postTypes' => PostType::all(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Rogue\Models\Action  $action
     */
    public function update(Request $request, Action $action)
    {
        $this->validate($request, [
            'name' => 'string',
            'post_type' => 'string|in:photo,voter-reg,text,share-social,phone-call',
            'callpower_campaign_id' => [
                'required_if:post_type,phone-call',
                Rule::unique('actions')->whereNotNull('callpower_campaign_id')->ignore($action->id),
            ],
            'reportback' => 'boolean',
            'civic_action' => 'boolean',
            'scholarship_entry' => 'boolean',
            'anonymous' => 'boolean',
            'noun' => 'string',
            'verb' => 'string',
        ]);

        $checkboxes = [
                        'reportback',
                        'civic_action',
                        'scholarship_entry',
                        'anonymous',
                      ];

        foreach ($checkboxes as $checkbox) {
            if (! isset($request[$checkbox])) {
                $request[$checkbox] = 0;
            }
        }

        $action->update($request->all());

        // Log that an action was updated.
        info('action_updated', ['id' => $action->id]);

        return redirect()->route('campaign-ids.show', $action->campaign_id);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \Rogue\Models\Action  $campaign
     */
    public function destroy(Action $action)
    {
        $action->forceDelete();

        // Log that an action was deleted.
        info('action_deleted', ['id' => $action->id]);

        return $this->respond('Action deleted.', 200);
    }
}
