<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cctv_cameras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->nullable()->constrained('stores')->nullOnDelete();
            $table->string('name'); // e.g. "CCTV Kasir Vol 2", "CCTV Display Area"
            $table->string('location_tag')->nullable(); // e.g. "Kasir", "Pintu Masuk", "Gudang"
            $table->string('stream_type')->default('webrtc'); // 'webrtc', 'hls', 'rtsp', 'web_portal'
            $table->text('stream_url')->nullable(); // direct stream URL (HLS / WebRTC / go2rtc / MediaMTX)
            $table->string('ip_address')->nullable(); // e.g. "192.168.1.50"
            $table->integer('port')->default(554); // default RTSP port
            $table->string('channel')->default('ch0'); // ch0 (HD) or ch1 (SD)
            $table->string('username')->nullable();
            $table->string('password')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('order_index')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cctv_cameras');
    }
};
