<?php

namespace App\Actions;

use App\Models\Transaction;
use Lorisleiva\Actions\Concerns\AsAction;
use Lorisleiva\Actions\ActionRequest;
use App\Data\DepositResponseData;
use App\Events\DepositConfirmed;
use App\Models\User;

class ConfirmDepositAction
{
    use AsAction;

    /**
     * @param array $validated
     * @return DepositResponseData
     */
    protected function deposit(array $validated): DepositResponseData
    {
        logger('ConfirmDepositAction@deposit');
        $response = DepositResponseData::from($validated);
        logger('$response = ');
        logger($response->toArray());
        $amountFloat = $response->amount;
        logger('$amountFloat = ');
        logger($amountFloat);

        /**  merchant details processing **/
            if (null == $user = User::where('mobile', $response->referenceCode)->first()) {
                logger('null == $user');
                if (strlen($merchant_code = $response->merchant_details->merchant_code) == 1) {
                    logger('$merchant_code = $response->merchant_details->merchant_code) == 1');
                    $user = User::where('meta->merchant->code', $merchant_code)->firstOrFail();
                    logger('$user = ');
                }
                logger('$user = ');
                logger($user->toArray());
            };
        /**             end              **/

        $transfer = TopupWalletAction::run($user, $amountFloat);
        $transfer->deposit->meta = $response->toArray();
        $transfer->deposit->save();
        DepositConfirmed::dispatch(Transaction::from($transfer->deposit));

        return $response;
    }

    /**
     * @param array $attribs
     * @return DepositResponseData
     */
    public function handle(array $attribs): DepositResponseData
    {
        return $this->deposit($attribs);
    }

    /**
     * @return array[]
     */
    public function rules(): array
    {
        return [
            'alias' =>  ['required', 'string'],
            'amount' => ['required', 'int', 'min:1'],
            'channel' =>  ['required', 'string'],
            'commandId' =>  ['required', 'int'],
            'externalTransferStatus' =>  ['required', 'string'],
            'operationId' =>  ['required', 'int'],
            'productBranchCode' =>  ['required', 'string'],
            'recipientAccountNumber' =>  ['required', 'string'],
            'recipientAccountNumberBankFormat' =>  ['required', 'string'],
//            'referenceCode' =>  ['required', 'string', 'exists:users,mobile'],
            'referenceCode' =>  ['required', 'string'],
            'referenceNumber' =>  ['required', 'string'],
            'registrationTime' =>  ['required', 'string'],
            'remarks' =>  ['required', 'string'],
            'sender' => ['required', 'array'],
            'sender.accountNumber' => ['required', 'string'],
            'sender.institutionCode' => ['required', 'string'],
            'sender.name' => ['required', 'string'],
            'transferType' =>  ['required', 'string'],
            'merchant_details' => ['nullable', 'array'],
            'merchant_details.merchant_code' => ['nullable', 'string'],
            'merchant_details.merchant_account' => ['nullable', 'string'],
        ];
    }

    /**
     * @param ActionRequest $request
     * @return \Illuminate\Http\Response
     */
    public function asController(ActionRequest $request): \Illuminate\Http\Response
    {
        $response = $this->deposit($request->validated());

        return response($response->toJson(), 200);
    }
}
