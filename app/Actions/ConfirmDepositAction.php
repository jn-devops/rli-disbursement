<?php

namespace App\Actions;

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
        $response = DepositResponseData::from($validated);
        $amountFloat = $response->amount;
        $mobile = $response->referenceCode;
        $user = User::where('mobile', $mobile)->firstOrFail();

        $transfer = TopupWalletAction::run($user, $amountFloat);
        $transfer->deposit->meta = $response->toArray();
        $transfer->deposit->save();
        DepositConfirmed::dispatch($transfer->deposit);

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
            'referenceCode' =>  ['required', 'string', 'exists:users,mobile'],
            'referenceNumber' =>  ['required', 'string'],
            'registrationTime' =>  ['required', 'string'],
            'remarks' =>  ['required', 'string'],
            'sender' => ['required', 'array'],
            'sender.accountNumber' => ['required', 'string'],
            'sender.institutionCode' => ['required', 'string'],
            'sender.name' => ['required', 'string'],
            'transferType' =>  ['required', 'string'],
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