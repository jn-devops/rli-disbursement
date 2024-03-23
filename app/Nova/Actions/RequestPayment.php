<?php

namespace App\Nova\Actions;

use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Fields\Text;

class RequestPayment extends TopupAccount
{
    protected string $title = 'Request Payment';

    public function fields(NovaRequest $request): array
    {
        return array_merge([
            Text::make('Account')
                ->rules(array_merge($this->action->rules()[$this->action::ACCOUNT_FIELD], ['required']))
                ->help('starts with "0" i.e., 09171234567')
        ], parent::fields($request));
    }
}
