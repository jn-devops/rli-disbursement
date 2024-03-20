<?php

namespace App\Actions;

use FrittenKeeZ\Vouchers\Facades\Vouchers;
use Lorisleiva\Actions\Concerns\AsAction;
use Illuminate\Console\Command;
use Carbon\CarbonInterval;

class GenerateServiceFeesCodeAction
{
    use AsAction;

    public string $commandSignature = 'outgoing:service-fees {transaction_fee} {merchant_discount_rate}';
    public string $commandDescription = 'Generate code to update the outgoing service fees.';
    public string $commandHelp = 'Additional message displayed when using the --help option.';

    /**
     * @param int $transaction_fee
     * @param int $merchant_discount_rate
     * @return string
     * @throws \Brick\Math\Exception\MathException
     * @throws \Brick\Math\Exception\NumberFormatException
     * @throws \Brick\Math\Exception\RoundingNecessaryException
     * @throws \Brick\Money\Exception\UnknownCurrencyException
     */
    public function handle(int $transaction_fee, int $merchant_discount_rate): string
    {
        $tf = $transaction_fee;
        $mdr = $merchant_discount_rate;
//        $meta = ['transaction_fee' => $tf, 'merchant_discount_rate' => $mdr];
        $meta = ['tf' => $tf, 'mdr' => $mdr];

        $voucher = Vouchers::withMask('****')
            ->withMetadata($meta)
            ->create();

        return $voucher->code;
    }

    /**
     * @param Command $command
     * @return void
     * @throws \Brick\Math\Exception\MathException
     * @throws \Brick\Math\Exception\NumberFormatException
     * @throws \Brick\Math\Exception\RoundingNecessaryException
     * @throws \Brick\Money\Exception\UnknownCurrencyException
     */
    public function asCommand(Command $command): void
    {
        $code = $this->handle(
            $command->argument('transaction_fee'),
            $command->argument('merchant_discount_rate')
        );

        $command->info($code);
    }
}
