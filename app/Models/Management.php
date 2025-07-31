<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Management
{

    public function searchParameters(string $parameters,string $value)
    {
        return $this->makeRequest([$parameters => $value]);
    }

    private function makeRequest(array $params)
    {
        $url = 'http://192.168.1.120:8007/';

        try {

            $response = Http::get($url, $params);

            if ($response->successful()) {
                return json_decode($response->body());
            }

            return null;
        } catch (\Exception $e) {
            \Log::error('Error en la consulta externa: ' . $e->getMessage());
            return null;
        }
    }


}

