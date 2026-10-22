<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ApiPeruService
{
    // apisperu.com (dniruc.apisperu.com). El plan gratuito da 1000 consultas al mes: cada documento se cachea para no gastar
    // consultas repitiendo el mismo DNI/RUC.
    private const CACHE_DAYS = 30;

    public function dni(string $numero): array
    {
        return Cache::remember("apisperu.dni.$numero", now()->addDays(self::CACHE_DAYS), function () use ($numero) {
            $data = $this->consultar("dni/$numero");

            if (empty($data['nombres'])) {
                throw new RuntimeException('No se encontró el DNI.');
            }

            return [
                'numero' => $numero,
                'nombre' => trim("{$data['nombres']} {$data['apellidoPaterno']} {$data['apellidoMaterno']}"),
            ];
        });
    }

    public function ruc(string $numero): array
    {
        return Cache::remember("apisperu.ruc.$numero", now()->addDays(self::CACHE_DAYS), function () use ($numero) {
            $data = $this->consultar("ruc/$numero");

            if (empty($data['razonSocial'])) {
                throw new RuntimeException('No se encontró el RUC.');
            }

            return [
                'numero' => $numero,
                'nombre' => $data['razonSocial'],
                'direccion' => $data['direccion'] ?? null,
            ];
        });
    }

    private function consultar(string $path): array
    {
        $token = config('services.apiperu.token');

        if (! $token) {
            throw new RuntimeException('Falta configurar APIPERU_TOKEN.');
        }

        $response = Http::withToken($token)
            ->acceptJson()
            ->timeout(10)
            ->get(config('services.apiperu.url') . '/' . $path);

        if (! $response->successful() || ($response->json('success') === false)) {
            throw new RuntimeException($response->json('message') ?? 'No se pudo consultar el documento.');
        }

        return $response->json();
    }
}
