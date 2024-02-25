<?php

namespace App\Actions;

use App\Data\{AmountData, DestinationAccountData, GatewayResponseData,  RecipientData};
use Lorisleiva\Actions\Concerns\AsAction;
use Illuminate\Support\Facades\Validator;
use Lorisleiva\Actions\ActionRequest;
use Illuminate\Support\Facades\Http;
use App\Classes\{Address, Gateway};
use Illuminate\Support\Arr;
use Brick\Money\Money;
use ReflectionMethod;
use Illuminate\Validation\Rule;

/**
 * Class RequestDisbursementAction
 *
 * @method   mixed    execute()
 */
class RequestDisbursementAction
{
    use AsAction;

    protected string $currency = 'PHP';

    public function __construct(protected Gateway $gateway)
    {
    }

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
        $response = Http::withHeaders($this->gateway->getHeaders())
            ->asJson()
            ->post(
                $this->gateway->getEndPoint(),
                [
                    "reference_id" => Arr::get($validated, 'reference'),
                    "settlement_rail" => Arr::get($validated, 'via'),
                    "amount" => $this->getMinorAmount($validated),
                    "source_account_number" => config('disbursement.source.account_number'),
                    "sender" => config('disbursement.source.sender'),
                    "destination_account" => $this->getDestinationAccount($validated),
                    "recipient" => $this->getRecipient($validated)
                ]
            );

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
        $min = config('disbursement.min');
        $min_rule = 'min:' . $min;
        $max = config('disbursement.max');
        $max_rule = 'max:' . $max;
        $settlement_rails = config('disbursement.settlement_rails');

        return [
            'reference' => ['required', 'string', 'min:2'],
            'bank' => ['required', 'string'],
            'account_number' => ['required', 'string'],
            'via' => ['required', 'string', Rule::in($settlement_rails)],
            'amount' => ['required', 'integer', $min_rule, $max_rule],
        ];
    }

    public function asController(ActionRequest $request): \Illuminate\Http\Response
    {
        $response = $this->disburse($request->validated());

        return response($response->toJson(), 200);
    }

    protected function getDestinationAccount(array $validated): array
    {
        return DestinationAccountData::from([
            'bank_code' => Arr::get($validated, 'bank'),
            'account_number' => Arr::get($validated, 'account_number')
        ])->toArray();
    }

    protected function getRecipient(array $validated): array
    {
        return RecipientData::from(
            [
                "name" => Arr::get($validated, 'account_number'),
                'address' => Address::generate()
            ]
        )->toArray();
    }

    protected function getMinorAmount(array $validated): array
    {
        $minor_amount = Money::of(Arr::get($validated, 'amount'), $this->currency)->getMinorAmount()->toInt();

        return AmountData::from([
            'cur' => $this->currency,
            'num' => (string) $minor_amount
        ])->toArray();
    }
}
