<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\JobImportService;
use Illuminate\Support\Facades\Http;

class FetchJobs extends Command
{
    protected $signature = 'jobs:fetch 
                            {--keywords=Developer,Programmer,Software Engineer} 
                            {--pages=10}';

    protected $description = 'Fetch jobs from JSearch API with pagination and multiple keywords';

    public function handle(JobImportService $importer)
    {
        $apiKey = env('JSEARCH_KEY');

        if (!$apiKey) {
            $this->error("Missing JSEARCH_KEY in .env");
            return;
        }

        $keywords = explode(',', $this->option('keywords'));
        $totalPages = (int) $this->option('pages');
        $imported = 0;

        foreach ($keywords as $keyword) {
            $keyword = trim($keyword);
            $this->info("Fetching jobs for keyword: {$keyword}");

            for ($page = 1; $page <= $totalPages; $page++) {

                $response = Http::retry(3, 1000)->withHeaders([
                    'X-API-KEY' => $apiKey,
                ])->get('https://api.openwebninja/jobs/search', [
                    'query' => $keyword,
                    'location' => 'Philippines',
                    'page' => $page,
                ]);

                if ($response->failed()) {
                    $this->error("[Page {$page}] Failed to fetch data.");
                    break;
                }

                $data = $response->json()['data'] ?? [];

                if (empty($data)) {
                    $this->info("No more data for {$keyword}");
                    break;
                }

                $importer->importFromApi($data);
                $imported += count($data);

                $this->info("Imported " . count($data) . " jobs from page {$page}");
                sleep(1); // avoid rate limit
            }
        }

        $this->info("🎉 Fetch Complete! Total Imported: {$imported} jobs.");
    }
}
