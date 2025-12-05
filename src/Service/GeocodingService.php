<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Service de géocodage utilisant Nominatim (OpenStreetMap)
 */
class GeocodingService
{
    public function __construct(
        private HttpClientInterface $httpClient
    ) {}

    /**
     * Convertir une adresse en coordonnées GPS
     */
    public function geocode(string $address): ?array
    {
        try {
            $response = $this->httpClient->request('GET', 'https://nominatim.openstreetmap.org/search', [
                'query' => [
                    'q' => $address,
                    'format' => 'json',
                    'limit' => 1,
                    // Pas de restriction de pays pour supporter les trajets internationaux
                ],
                'headers' => [
                    'User-Agent' => 'WayZo VTC App/1.0',
                ],
            ]);

            $data = $response->toArray();

            if (!empty($data) && isset($data[0]['lat'], $data[0]['lon'])) {
                return [
                    'lat' => (float) $data[0]['lat'],
                    'lng' => (float) $data[0]['lon'],
                    'displayName' => $data[0]['display_name'] ?? $address,
                ];
            }

            return null;
        } catch (\Exception $e) {
            // Log l'erreur mais ne pas bloquer
            return null;
        }
    }

    /**
     * Géocoder le départ et la destination d'une course
     */
    public function geocodeRide(string $depart, string $destination): array
    {
        return [
            'departure' => $this->geocode($depart),
            'arrival' => $this->geocode($destination),
        ];
    }
}
