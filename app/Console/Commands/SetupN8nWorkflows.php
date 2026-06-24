<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\File;

class SetupN8nWorkflows extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'n8n:setup {--key= : The n8n Public API key}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automate setting up the ClamAV, AI analysis, and HITL notification workflows in n8n';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $apiKey = $this->option('key') ?: env('N8N_API_KEY', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiJiOWI2NjFhZi1iMjc4LTQ5OTAtOTk5OC03ZGJhMTk1MzE3Y2IiLCJpc3MiOiJuOG4iLCJhdWQiOiJwdWJsaWMtYXBpIiwianRpIjoiOGUyOGI4MjMtZjA5ZC00MTQzLTk2NjMtYTgwZmNmZDVhMmI3IiwiaWF0IjoxNzgxODY4MjU5LCJleHAiOjE3ODk2MTc2MDB9.y_xv6cuSYqD4JhYQmMqg5RfhQl5v2-XhT28QFpKqVzA');
        $n8nUrl = 'http://localhost:5678/api/v1';

        $this->info("Connecting to n8n at {$n8nUrl}...");

        try {
            // Test connection to n8n API
            $response = Http::withHeaders([
                'X-N8N-API-KEY' => $apiKey
            ])->get("{$n8nUrl}/workflows");

            if ($response->failed()) {
                $this->error("Failed to connect to n8n API. Status code: " . $response->status());
                $this->error($response->body());
                return 1;
            }

            $existingWorkflows = collect($response->json('data') ?? []);
            $this->info("Successfully connected! Found " . $existingWorkflows->count() . " existing workflow(s).");

            // Setup/Retrieve Mailpit SMTP Credentials
            $credResponse = Http::withHeaders([
                'X-N8N-API-KEY' => $apiKey
            ])->get("{$n8nUrl}/credentials");

            $smtpCredentialId = null;
            if ($credResponse->successful()) {
                $existingCreds = collect($credResponse->json('data') ?? []);
                $smtpCred = $existingCreds->first(function ($c) {
                    return ($c['type'] ?? '') === 'smtp' && ($c['name'] ?? '') === 'Mailpit SMTP';
                });

                if ($smtpCred) {
                    $smtpCredentialId = $smtpCred['id'];
                    $this->info("Found existing Mailpit SMTP credential with ID: {$smtpCredentialId}");
                }
            }

            if (!$smtpCredentialId) {
                $this->info("Creating Mailpit SMTP credential in n8n...");
                $createCredResponse = Http::withHeaders([
                    'X-N8N-API-KEY' => $apiKey
                ])->post("{$n8nUrl}/credentials", [
                    'name' => 'Mailpit SMTP',
                    'type' => 'smtp',
                    'data' => [
                        'host' => '127.0.0.1',
                        'port' => 1025,
                        'secure' => false,
                        'disableStartTls' => true,
                        'user' => '',
                        'password' => '',
                    ],
                ]);

                if ($createCredResponse->successful()) {
                    $smtpCredentialId = $createCredResponse->json('id');
                    $this->info("Successfully created Mailpit SMTP credential with ID: {$smtpCredentialId}");
                } else {
                    $this->error("Failed to create Mailpit SMTP credential: " . $createCredResponse->body());
                    return 1;
                }
            }

            $workflowDirectory = database_path('n8n');
            if (!File::exists($workflowDirectory)) {
                $this->error("Workflow directory not found at {$workflowDirectory}.");
                return 1;
            }

            $files = File::files($workflowDirectory);
            foreach ($files as $file) {
                if ($file->getExtension() !== 'json') {
                    continue;
                }

                $this->info("Processing workflow file: {$file->getFilename()}");
                $workflowJson = File::get($file->getRealPath());

                // Replace local URL placeholders with Docker internal hostnames
                $appInternalUrl = env('APP_INTERNAL_URL', 'http://host.docker.internal:8000');
                $workflowJson = str_replace(
                    ['http://127.0.0.1:8000', 'http://localhost:8000'],
                    $appInternalUrl,
                    $workflowJson
                );

                $workflowJson = str_replace(
                    ['http://127.0.0.1:9000', 'http://localhost:9000'],
                    'http://host.docker.internal:9000',
                    $workflowJson
                );

                $workflowData = json_decode($workflowJson, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    $this->error("Invalid JSON format in {$file->getFilename()}.");
                    continue;
                }

                // Inject the actual SMTP credential ID into Send Email nodes if present
                if ($smtpCredentialId && isset($workflowData['nodes'])) {
                    foreach ($workflowData['nodes'] as &$node) {
                        if (($node['type'] ?? '') === 'n8n-nodes-base.emailSend') {
                            if (isset($node['credentials']['smtp'])) {
                                $node['credentials']['smtp']['id'] = $smtpCredentialId;
                            }
                        }
                    }
                }

                $name = $workflowData['name'] ?? null;
                if (!$name) {
                    $this->error("Workflow name missing in {$file->getFilename()}.");
                    continue;
                }

                // Check if a workflow with the same name already exists
                $matched = $existingWorkflows->firstWhere('name', $name);

                if ($matched) {
                    $this->info("Updating existing workflow '{$name}' (ID: {$matched['id']})...");
                    $updateResponse = Http::withHeaders([
                        'X-N8N-API-KEY' => $apiKey
                    ])->put("{$n8nUrl}/workflows/{$matched['id']}", [
                        'name' => $name,
                        'nodes' => $workflowData['nodes'] ?? [],
                        'connections' => $workflowData['connections'] ?? [],
                        'settings' => $workflowData['settings'] ?? [],
                    ]);

                    if ($updateResponse->successful()) {
                        $this->info("Workflow '{$name}' updated successfully. Activating...");
                        $id = $updateResponse->json('id') ?: $matched['id'];
                        $activateResponse = Http::withHeaders([
                            'X-N8N-API-KEY' => $apiKey
                        ])->post("{$n8nUrl}/workflows/{$id}/activate", (object)[]);

                        if ($activateResponse->successful()) {
                            $this->info("Workflow '{$name}' activated successfully.");
                        } else {
                            $this->warn("Failed to activate workflow '{$name}': " . $activateResponse->body());
                        }
                    } else {
                        $this->error("Failed to update workflow '{$name}': " . $updateResponse->body());
                    }
                } else {
                    $this->info("Creating new workflow '{$name}'...");
                    $createResponse = Http::withHeaders([
                        'X-N8N-API-KEY' => $apiKey
                    ])->post("{$n8nUrl}/workflows", [
                        'name' => $name,
                        'nodes' => $workflowData['nodes'] ?? [],
                        'connections' => $workflowData['connections'] ?? [],
                        'settings' => $workflowData['settings'] ?? [],
                    ]);

                    if ($createResponse->successful()) {
                        $this->info("Workflow '{$name}' created successfully. Activating...");
                        $id = $createResponse->json('id');
                        $activateResponse = Http::withHeaders([
                            'X-N8N-API-KEY' => $apiKey
                        ])->post("{$n8nUrl}/workflows/{$id}/activate", (object)[]);

                        if ($activateResponse->successful()) {
                            $this->info("Workflow '{$name}' activated successfully.");
                        } else {
                            $this->warn("Failed to activate workflow '{$name}': " . $activateResponse->body());
                        }
                    } else {
                        $this->error("Failed to create workflow '{$name}': " . $createResponse->body());
                    }
                }
            }

            $this->info("n8n workflow setup completed successfully.");
            return 0;

        } catch (\Exception $e) {
            $this->error("An error occurred during workflow setup: " . $e->getMessage());
            return 1;
        }
    }
}
