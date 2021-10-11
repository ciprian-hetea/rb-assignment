<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReportIndexRequest extends FormRequest
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
            'period' => [
                'string',
                'nullable',
                function ($attribute, $value, $fail) {
                    try {
                        $date = \Carbon\Carbon::parse("{$value} ago");
                    } catch (\Exception $e) {
                        $fail("The {$attribute} is not a valid period.");
                    }
                },
            ]
        ];
    }
}
