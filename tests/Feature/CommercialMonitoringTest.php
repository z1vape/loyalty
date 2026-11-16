<?php

namespace Tests\Feature;

use App\Models\CctvCamera;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommercialMonitoringTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Store $store;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::create([
            'name' => 'Z1 VAPE STORE VOL 2',
            'address' => 'Jakarta',
            'phone' => '08123456789',
        ]);

        $this->user = User::factory()->create();
        $this->user->stores()->attach($this->store->id);
    }

    public function test_user_can_access_commercial_monitoring(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession(['active_store_id' => $this->store->id])
            ->get('/commercial/monitoring');

        $response->assertStatus(200);
        $response->assertSee('Monitoring CCTV Toko');
        $response->assertSee('Z1 VAPE STORE VOL 2');
    }

    public function test_user_can_create_cctv_camera(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession(['active_store_id' => $this->store->id])
            ->post('/commercial/monitoring/cameras', [
                'name' => 'CCTV Kasir Vol 2',
                'store_id' => $this->store->id,
                'location_tag' => 'Kasir',
                'stream_type' => 'rtsp',
                'ip_address' => '192.168.1.50',
                'port' => 554,
                'channel' => 'ch0',
                'username' => 'admin',
                'password' => 'secret123',
                'notes' => 'Kamera Bardi PTZ area kasir',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('cctv_cameras', [
            'name' => 'CCTV Kasir Vol 2',
            'ip_address' => '192.168.1.50',
            'port' => 554,
            'channel' => 'ch0',
        ]);

        $camera = CctvCamera::first();
        $this->assertEquals('rtsp://admin:secret123@192.168.1.50:554/live/ch0', $camera->rtsp_url);
    }

    public function test_user_can_update_cctv_camera(): void
    {
        $camera = CctvCamera::create([
            'store_id' => $this->store->id,
            'name' => 'CCTV Lama',
            'stream_type' => 'rtsp',
            'ip_address' => '192.168.1.10',
            'port' => 554,
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['active_store_id' => $this->store->id])
            ->put("/commercial/monitoring/cameras/{$camera->id}", [
                'name' => 'CCTV Etalase Depan',
                'store_id' => $this->store->id,
                'stream_type' => 'rtsp',
                'ip_address' => '192.168.1.55',
                'port' => 554,
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();
        $this->assertDatabaseHas('cctv_cameras', [
            'id' => $camera->id,
            'name' => 'CCTV Etalase Depan',
            'ip_address' => '192.168.1.55',
        ]);
    }

    public function test_user_can_delete_cctv_camera(): void
    {
        $camera = CctvCamera::create([
            'store_id' => $this->store->id,
            'name' => 'CCTV Rusak',
            'stream_type' => 'rtsp',
            'ip_address' => '192.168.1.99',
            'port' => 554,
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['active_store_id' => $this->store->id])
            ->delete("/commercial/monitoring/cameras/{$camera->id}");

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();
        $this->assertDatabaseMissing('cctv_cameras', ['id' => $camera->id]);
    }
}
