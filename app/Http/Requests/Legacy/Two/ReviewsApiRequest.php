<?php

namespace Rogue\Http\Requests\Legacy\Two;

use Rogue\Http\Requests\Request;

class ReviewsApiRequest extends Request
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'post_id' => 'required',
            'status' => 'in:pending,accepted,rejected',
            'admin_northstar_id' => 'required',
        ];
    }
}