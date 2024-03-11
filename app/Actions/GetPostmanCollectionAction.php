<?php

namespace App\Actions;

use Lorisleiva\Actions\Concerns\AsAction;

class GetPostmanCollectionAction
{
    use AsAction;

    /**
     * @return array
     */
    public function handle(): array
    {
        $json_file = 'nLITn.postman_collection.json';
        $path = documents_path($json_file);

        return file_exists($path) ? json_decode(file_get_contents($path), true) : [];
    }

    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Foundation\Application|\Illuminate\Http\Response
     */
    public function asController(): \Illuminate\Foundation\Application|\Illuminate\Http\Response|\Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory
    {
        return response($this->handle(), 200, ['Content-type' => 'application/json']);
    }
}
