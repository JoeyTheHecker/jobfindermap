<?php

namespace App\Services;

use App\Models\JobListing;

class JobImportService
{
    protected $regions = [
        'Luzon' => ['Manila','Makati','Quezon City','Pasig','Cavite','Laguna','Bulacan','Pampanga'],
        'Visayas' => ['Cebu','Iloilo','Bacolod','Tacloban','Dumaguete'],
        'Mindanao' => ['Davao','Cagayan de Oro','Zamboanga','General Santos'],
    ];

    public function detectRegion($city)
    {
        if (!$city) return null;

        foreach ($this->regions as $name => $cities) {
            if (in_array($city, $cities)) {
                return $name;
            }
        }

        return null; // default fallback
    }

    public function importFromApi($jobs)
    {
        foreach ($jobs as $job) {

            $city = $job['location']['city'] ?? null;

            JobListing::updateOrCreate(
                ['external_id' => $job['id']],
                [
                    'title' => $job['title'],
                    'company' => $job['company'] ?? null,
                    'city' => $city,
                    'province' => $job['location']['state'] ?? null,
                    'region' => $this->detectRegion($city),
                    'lat' => $job['location']['lat'] ?? null,
                    'lng' => $job['location']['lng'] ?? null,
                    'raw_location' => $job['location']['formatted'] ?? null,
                ]
            );
        }
    }
}


?>