<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Account;
use App\Models\Product;

    /**
     * @SuppressWarnings(PHPMD.ElseExpression)
     */

class StoreInvoice extends FormRequest
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

    public function messages()
    {
        return [
            'customer_id.required' => 'The customer field is required.',
            'customer_id.exists' =>
                'The selected customer is invalid. Please choose among the recommended items.',
            'invoice_number.min' => 'The invoice number must be a positive number.',
        ];
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'customer_id' => ['required', 'exists:App\Models\Customer,id'],
            'date' => ['required', 'date'],
            'due_date' => ['required', 'date'],
            'invoice_number' => ['required', 'min:1'],
            "item_lines.'product_id'" => ['sometimes'],
            "item_lines.'product_id'.*" => [
                'sometimes'
            ],
            "item_lines.'quantity'.*" => ['sometimes'],
            "item_lines.'amount'.*" => ['sometimes'],
            "item_lines.'input_tax'.*" => ['sometimes'],
        ];
    }
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if (!is_int(filter_var(request('invoice_number'), FILTER_VALIDATE_INT))) {
                $validator->errors()->add('invoice_number', 'Invoice number must be an integer.');
            }
            if (is_null(request("item_lines.'product_id'"))) {
                $validator->errors()->add('lines', 'Please enter at least one line item.');
            }
            if (!is_null(request("item_lines.'product_id'"))) {
                $invoice = new StoreBill();
                $invoice->validateItemLines($validator);
            }
        });
    }
}
