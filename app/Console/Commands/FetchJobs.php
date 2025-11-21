<?php

namespace App\Console\Commands;
use Illuminate\Console\Command;
use App\Services\JobImportService;
use Illuminate\Support\Facades\Http;

class FetchJobs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jobs:fetch';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch jobs from JSearch API';

    /**
     * Execute the console command.
     */
    public function handle(JobImportService $importer)
    {
        $response = Http::withHeaders([
            'X-API-KEY' => env('JSEARCH_KEY'),
        ])->get('https://api.openweb ninja/jobs/search', [
            'query' => 'Developer',
            'location' => 'Philippines',
            'num_pages' => 2,
        ]);

        if ($response->failed()) {
            $this->error("Failed to fetch jobs");
            return;
        }

        $data = $response->json()['data'] ?? [];

        $importer->importFromApi($data);

        $this->info("Imported " . count($data) . " jobs.");
    }
}
