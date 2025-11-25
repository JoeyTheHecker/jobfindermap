<?php

namespace App\Services;

use App\Models\JobListing;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class JobImportService
{
    /**
     * Cities grouped into regions for classification.
     * Use lowercase values to simplify matching.
     */
    protected array $regions = [
        'Luzon' => [
            'manila','makati','quezon city','qc','pasig','cainta',
            'cavite','tagaytay','laguna','bulacan','pampanga','antipolo'
        ],
        'Visayas' => [
            'cebu','mandaue','lapu-lapu','iloilo','bacolod',
            'tacloban','dumaguete','ormoc','roxas'
        ],
        'Mindanao' => [
            'davao','cagayan de oro','cd0','zamboanga','general santos','gensan',
            'butuan','kidapawan'
        ],
    ];

    /**
     * Detect region using partial matching (more reliable than exact match).
     */
    public function detectRegion(?string $city): ?string
    {
        if (!$city) {
            return null;
        }

        $cityLower = strtolower($city);

        foreach ($this->regions as $region => $cities) {
            foreach ($cities as $knownCity) {
                if (Str::contains($cityLower, strtolower($knownCity))) {
                    return $region;
                }
            }
        }

        return null;
    }

    /**
     * Safely parse job coordinates from API.
     */
    protected function extractCoordinates(array $location): array
    {
        $lat = $location['lat'] ?? $location['latitude'] ?? null;
        $lng = $location['lng'] ?? $location['longitude'] ?? null;

        // Convert to float or set null
        return [
            'lat' => $lat ? (float) $lat : null,
            'lng' => $lng ? (float) $lng : null,
        ];
    }

    /**
     * Import jobs from API into database with upsert.
     */
    public function importFromApi(array $jobs): void
    {
        foreach ($jobs as $job) {

            $externalId = $job['id'] ?? null;
            if (!$externalId) {
                Log::warning("Skipped job with no external ID");
                continue;
            }

            // Extract location block safely
            $location = $job['location'] ?? [];
            $city = $location['city'] ?? ($location['formatted'] ?? null);

            // Extract coordinates
            $coords = $this->extractCoordinates($location);

            // Skip if no lat/lng (required for heatmap)
            if (!$coords['lat'] || !$coords['lng']) {
                Log::info("Job skipped — no coordinates: ID {$externalId}");
                continue;
            }

            JobListing::updateOrCreate(
                ['external_id' => $externalId],
                [
                    'title'        => $job['title'] ?? 'Untitled Job',
                    'company'      => $job['company'] ?? null,
                    'city'         => $city,
                    'province'     => $location['state'] ?? null,
                    'region'       => $this->detectRegion($city),
                    'lat'          => $coords['lat'],
                    'lng'          => $coords['lng'],
                    'raw_location' => $location['formatted'] ?? null,
                ]
            );
        }
    }
}
