<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\{Arr, Str};
use App\Classes\Address;
use Tests\TestCase;

class ZipCodesTest extends TestCase
{
    use WithFaker;
    /**
     * A basic test example.
     */
    public function test_zip_codes_address(): void
    {
        $this->markTestSkipped('zip code doodle');
//        $json_file = 'zip_codes_list.json';
//        $json_path = documents_path($json_file);
//        $this->assertTrue(file_exists($json_path));
//        $array = json_decode(file_get_contents($json_path), true);
//        $first_two = random_int(10,90);
//        $city = $array[$first_two . '00'];
//        $city = is_array($city) ? implode(' ', $city) : $city;
//        $last_two = '0' . random_int(1,5);
//        $zip_code = $first_two . $last_two;
//        $address1 = $array[$zip_code];
//        $address1 = is_array($address1) ? implode(' ', $address1) : $address1;
//        $country = 'PH';
//        $address = compact('address1', 'city', 'country', 'zip_code');
    }

    public function test_address(): void
    {
        $this->markTestSkipped('generate address doodle');
//        dd(Address::generate());
    }
}
