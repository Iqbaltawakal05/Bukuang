<?php

namespace Tests\Feature;

use App\Jobs\GenerateExportJob;
use App\Models\Category;
use App\Models\Export;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class ExportTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $otherUser;
    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();

        $this->category = Category::create([
            'user_id' => $this->user->id,
            'name' => 'Salary',
            'type' => 'income',
            'icon' => 'money',
            'color' => '#00FF00',
        ]);

        Transaction::create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'type' => 'income',
            'amount' => 5000000,
            'transaction_date' => '2026-08-01',
            'description' => 'Gaji Pokok',
        ]);
    }

    public function test_user_can_request_export_job(): void
    {
        $payload = [
            'format' => 'csv',
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-31',
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/exports', $payload);

        $response->assertStatus(Response::HTTP_ACCEPTED)
            ->assertJsonPath('message', 'Permintaan ekspor laporan berhasil dibuat dan sedang diproses.')
            ->assertJsonPath('data.format', 'csv');

        $this->assertDatabaseHas('exports', [
            'user_id' => $this->user->id,
            'format' => 'csv',
        ]);
    }

    public function test_generate_export_job_creates_csv_file(): void
    {
        $export = Export::create([
            'user_id' => $this->user->id,
            'format' => 'csv',
            'status' => 'pending',
        ]);

        $job = new GenerateExportJob($export, []);
        $job->handle();

        $export->refresh();
        $this->assertEquals('completed', $export->status);
        $this->assertNotNull($export->file_path);

        Storage::disk('local')->assertExists($export->file_path);
    }

    public function test_generate_export_job_creates_xlsx_file(): void
    {
        $export = Export::create([
            'user_id' => $this->user->id,
            'format' => 'xlsx',
            'status' => 'pending',
        ]);

        $job = new GenerateExportJob($export, []);
        $job->handle();

        $export->refresh();
        $this->assertEquals('completed', $export->status);
        Storage::disk('local')->assertExists($export->file_path);
    }

    public function test_generate_export_job_creates_pdf_file(): void
    {
        $export = Export::create([
            'user_id' => $this->user->id,
            'format' => 'pdf',
            'status' => 'pending',
        ]);

        $job = new GenerateExportJob($export, []);
        $job->handle();

        $export->refresh();
        $this->assertEquals('completed', $export->status);
        Storage::disk('local')->assertExists($export->file_path);
    }

    public function test_user_can_list_own_exports(): void
    {
        Export::create([
            'user_id' => $this->user->id,
            'format' => 'csv',
            'status' => 'completed',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/exports');

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(1, 'data');
    }

    public function test_user_can_download_completed_export(): void
    {
        $filePath = 'exports/test_download.csv';
        Storage::disk('local')->put($filePath, 'ID,Date,Type,Category,Amount');

        $export = Export::create([
            'user_id' => $this->user->id,
            'format' => 'csv',
            'status' => 'completed',
            'file_name' => 'test_download.csv',
            'file_path' => $filePath,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/exports/{$export->id}/download");

        $response->assertStatus(Response::HTTP_OK);
    }

    public function test_user_cannot_access_other_users_export(): void
    {
        $export = Export::create([
            'user_id' => $this->user->id,
            'format' => 'csv',
            'status' => 'completed',
        ]);

        $response = $this->actingAs($this->otherUser)
            ->getJson("/api/v1/exports/{$export->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }
}
