<?php

namespace Tests\Unit;

use App\Collections\SettlementRailCollection;
use Spatie\LaravelData\DataCollection;
use App\Data\{BankData, SettlementRailData};
use Tests\TestCase;

class BankTest extends TestCase
{
    public function test_settlement_rail_data_collection(): void
    {
        $collection = SettlementRailData::collect([
            ['name' => 'INSTAPAY', 'bank_code' => 'ZBTEPHM2XXX'],
            ['name' => 'PESONET', 'bank_code' => 'ZBTEPHM2XXX'],
        ]);

        $this->assertIsArray($collection);
        foreach ($collection as $ndx => $data) {
            $this->assertInstanceOf(SettlementRailData::class, $data);
            switch ($ndx) {
                case 0:
                    $this->assertEquals('INSTAPAY', $data->name);
                    $this->assertEquals('ZBTEPHM2XXX', $data->code);
                    break;
                case 1:
                    $this->assertEquals('PESONET', $data->name);
                    $this->assertEquals('ZBTEPHM2XXX', $data->code);
                    break;
            }
        }
    }

    public function test_bank_data(): void
    {
        $data = BankData::from(
            [
                'full_name' => 'INSTAPAY',
                'swift_bic' => 'ZBTEPHM2XXX',
                'settlement_rail' => [
                    ['name' => 'INSTAPAY', 'bank_code' => 'ZBTEPHM2XXX'],
                    ['name' => 'PESONET', 'bank_code' => 'ZBTEPHM2XXX'],
                ]
            ],
        );
        $this->assertInstanceOf(DataCollection::class, $data->settlement_rail);
        foreach ($data->settlement_rail as $rail) {
            $this->assertInstanceOf(SettlementRailData::class, $rail);

        }
    }

    public function test_banks_list(): void
    {
        $json_file = 'banks_list.json';
        $json_path = documents_path($json_file);
        $this->assertTrue(file_exists($json_path));
        $array = json_decode(file_get_contents($json_path), true);
        $data_array1 = BankData::collect($array);
        $data_array2 = BankData::collectFromJsonFile($json_file);
        $this->assertEquals($data_array1, $data_array2);
        $bank = $data_array2['GXCHPHM2XXX'];
        $this->assertInstanceOf(BankData::class, $bank);
        $this->assertEquals('GXCHPHM2XXX', $bank->code);
        $this->assertEquals('G-Xchange / GCash', $bank->name);
        foreach ($bank->settlement_rail as $ndx => $rail) {
            switch ($ndx) {
                case 0:
                    $this->assertEquals('INSTAPAY', $rail->name);
                    $this->assertEquals('GXCHPHM2XXX', $rail->code);
                    break;
                case 1:
                    $this->assertEquals('PESONET', $rail->name);
                    $this->assertEquals('GXCHPHM2XXX', $rail->code);
                    break;
            }
        }
        $this->expectException(\ErrorException::class);
        $data_array1['asdsadsa'];
    }
}
