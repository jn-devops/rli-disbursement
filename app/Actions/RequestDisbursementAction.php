<?php

namespace App\Actions;

use Brick\Money\Money;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Fluent;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsAction;
use Illuminate\Support\Facades\Validator;
use ReflectionMethod;
use ReflectionProperty;
use Illuminate\Support\Facades\Http;
use App\Classes\Gateway;
use Illuminate\Support\{Arr, Str};
use App\Data\GatewayResponseData;
use App\Data\RecipientData;
use App\Data\ContactData;
use App\Data\DestinationAccountData;
use App\Data\AmountData;
use App\Classes\Address;

/**
 * Class RequestDisbursementAction
 *
 * @method   mixed    execute()
 */
class RequestDisbursementAction
{
    use AsAction;

    public function __construct(protected Gateway $gateway){}

    public static function execute(...$arguments): mixed
    {
        $action = static::make();
        $argName = $action->validated ?? 'validated';
        foreach ((new ReflectionMethod(static::class, 'handle'))->getParameters() as $parameter) {
            if (($parameter->name == $argName) and ($parameter->getType() == 'array')) {
                $attribs = func_get_args()[$parameter->getPosition()];

                return $action->handle(Validator::validate($attribs, $action->rules()));
            }
        }

        return $action->handle(...$arguments);
    }

    protected function disburse(array $validated): GatewayResponseData|bool
    {
        $reference = Arr::get($validated, 'reference');
        $via = Arr::get($validated, 'via');
        $currency = 'PHP';
        $amount =  Money::of(Arr::get($validated, 'amount'), $currency)->getMinorAmount()->toInt();
        $account_number = config('disbursement.source.account_number');
        $sender = config('disbursement.source.sender');
        $destination_account = DestinationAccountData::from([
            'bank_code' => Arr::get($validated, 'bank'),
            'account_number' => Arr::get($validated, 'account_number')
        ])->toArray();
        $recipient = RecipientData::from(
            [
                "name" => Arr::get($validated, 'account_number'),
                'address' => Address::generate()
            ]
        )->toArray();
        $amount = AmountData::from([
            "cur" => $currency,
            "num" => (string) $amount
        ])->toArray();

        $body = [
            "reference_id" => $reference,
            "settlement_rail" => $via,
            "amount" => $amount,
            "source_account_number" => $account_number,
            "sender" => $sender,
            "destination_account" => $destination_account,
            "recipient" => $recipient
        ];

        $response = Http::withHeaders($this->gateway->getHeaders())->asJson()->post($this->gateway->getEndPoint(), $body);

        return $response->successful() ? GatewayResponseData::from($response->json()) : false;
    }

    public function handle(array $validated): GatewayResponseData|bool
    {
        return $this->disburse($validated);
    }

    /**
     * @return array[]
     */
    public function rules(): array
    {
        return [
            'reference' => ['required', 'string', 'min:2'],
            'bank' => ['required', 'string'],
            'account_number' => ['required', 'string'],
            'via' => ['required', 'string'],
            'amount' => ['required', 'integer', 'min:1', 'max:2'],
        ];
    }

    public function asController(ActionRequest $request): \Illuminate\Http\Response
    {
        $response = $this->disburse($request->validated());

        return response($response->toJson(), 200);
    }
}
