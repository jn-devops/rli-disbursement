<?php

namespace App\Nova;

use Laravel\Nova\Fields\{Boolean, Currency, DateTime, ID, Text};
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Actions\ExportAsCsv;
use Illuminate\Support\{Arr, Str};
use Laravel\Nova\Fields\MorphTo;
use Illuminate\Http\Request;
use App\Data\BankData;
use App\Models\User;

class Transaction extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\Bavix\Wallet\Models\Transaction>
     */
    public static $model = \Bavix\Wallet\Models\Transaction::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'id';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'id',
    ];

    /**
     * The visual style used for the table. Available options are 'tight' and 'default'.
     *
     * @var string
     */
    public static $tableStyle = 'tight';

    /**
     * Indicates if the resource should be displayed in the sidebar.
     *
     * @var bool
     */
    public static $displayInNavigation = false;

    /**
     * Get the fields displayed by the resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function fields(NovaRequest $request)
    {
        return [
            ID::make()->sortable(),
            Text::make('Type')->sortable(),
            MorphTo::make('Payable')->hideFromIndex(),
            Currency::make('Amount')->asMinorUnits()->currency('PHP')->sortable(),
//            Text::make('Via', 'meta->details->settlement_rail')->sortable(),
            Text::make('Via', function ($attribute) use ($request) {
                return match($this->getAttribute('type')) {
                    'withdraw' => $request->json('meta->details->settlement_rail'),
                    'deposit' =>  Arr::get($this->getAttribute('meta'), 'channel'),
                };
            })->sortable(),
//            Text::make('Bank', 'meta->details->destination_account->bank_code')->sortable(),
            Text::make('Account Holder', function ($attribute) use ($request) {
                $holder = match($this->getAttribute('type')) {
                    'withdraw' => $request->json('meta->details->destination_account->bank_code'),
                    'deposit' =>  Arr::get($this->getAttribute('meta'), 'sender.name'),
                };

                return Str::title($holder);
            })->sortable(),
//            Text::make('Account #', 'meta->details->destination_account->account_number')->sortable(),
            Text::make('Account Id', function ($attribute) use ($request) {
                return match($this->getAttribute('type')) {
                    'withdraw' => $request->json('meta->details->destination_account->account_number'),
                    'deposit' =>  $this->getInstitution($request) . ' - ' . $this->getAccount($request),
                };
            })->sortable(),
//            Currency::make('Sent', function($attribute) use ($request) {
//                return $request->json('meta->details->amount');
//            })->asMinorUnits()->currency('PHP')->sortable(),
            Text::make('OperationId', 'meta->operationId')->sortable()->hideFromIndex(),
            Boolean::make('Confirmed')->sortable(),
            DateTime::make('Created', 'created_at')->sortable()->hideFromIndex(),
            DateTime::make('Updated', 'updated_at')->sortable(),
//            DateTime::make('Created', 'created_at')->withFriendlyDate()->sortable()->hideFromIndex(),
//            DateTime::make('Updated', 'updated_at')->withFriendlyDate()->sortable(),
        ];
    }

    /**
     * Get the cards available for the request.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function cards(NovaRequest $request)
    {
        return [];
    }

    /**
     * Get the filters available for the resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function filters(NovaRequest $request)
    {
        return [];
    }

    /**
     * Get the lenses available for the resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function lenses(NovaRequest $request)
    {
        return [];
    }

    /**
     * Get the actions available for the resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function actions(NovaRequest $request)
    {
        return [
            ExportAsCsv::make()->nameable(),
        ];
    }

    public static function authorizedToCreate(Request $request)
    {
        return false;
    }

    public function authorizedToDelete(Request $request)
    {
        return false;
    }

    public function authorizedToUpdate(Request $request)
    {
        return false;
    }

    protected function getInstitution(NovaRequest $request): string
    {
        $institution = null;
        $bank_date = BankData::collectFromJsonFile('banks_list.json'); //TODO: put this in cache
        $institution_code = Arr::get($this->getAttribute('meta'), 'sender.institutionCode');
        if ($bank = Arr::get($bank_date, $institution_code))
            $institution = $bank->name;

        return Str::upper($institution ?: $institution_code);
    }

    protected function getAccount(NovaRequest $request): string
    {
        $account = '-';
        if (($user = $this->payable) instanceof User) {
            if ($user->mobile === $reference_code = Arr::get($this->getAttribute('meta'), 'referenceCode')) {
                $account = $reference_code;
            }
            else {
                $account = Arr::get($this->getAttribute('meta'), 'merchant_details.merchant_account');
            }
        }

        return $account;
    }
}
