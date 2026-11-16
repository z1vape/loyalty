<?php

namespace Database\Seeders;

use App\Models\AttendanceStatus;
use App\Models\Shift;
use Illuminate\Database\Seeder;

class ShiftAndAttendanceStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statuses = [
            [
                'code'                => 'PRS',
                'name'                => 'Hadir Tepat Waktu',
                'type'                => 'present',
                'badge_color'         => 'emerald',
                'counts_as_workday'   => true,
                'requires_attachment' => false,
                'is_system'           => true,
                'is_active'           => true,
            ],
            [
                'code'                => 'LATE',
                'name'                => 'Terlambat',
                'type'                => 'late',
                'badge_color'         => 'amber',
                'counts_as_workday'   => true,
                'requires_attachment' => false,
                'is_system'           => true,
                'is_active'           => true,
            ],
            [
                'code'                => 'IZN',
                'name'                => 'Izin',
                'type'                => 'leave',
                'badge_color'         => 'sky',
                'counts_as_workday'   => false,
                'requires_attachment' => false,
                'is_system'           => true,
                'is_active'           => true,
            ],
            [
                'code'                => 'SKT',
                'name'                => 'Sakit',
                'type'                => 'sick',
                'badge_color'         => 'orange',
                'counts_as_workday'   => true,
                'requires_attachment' => true,
                'is_system'           => true,
                'is_active'           => true,
            ],
            [
                'code'                => 'CTI',
                'name'                => 'Cuti Tahunan',
                'type'                => 'leave',
                'badge_color'         => 'purple',
                'counts_as_workday'   => true,
                'requires_attachment' => false,
                'is_system'           => true,
                'is_active'           => true,
            ],
            [
                'code'                => 'OFF',
                'name'                => 'Libur (Jadwal OFF)',
                'type'                => 'holiday',
                'badge_color'         => 'slate',
                'counts_as_workday'   => false,
                'requires_attachment' => false,
                'is_system'           => true,
                'is_active'           => true,
            ],
            [
                'code'                => 'ALP',
                'name'                => 'Alpha / Tidak Hadir',
                'type'                => 'absent',
                'badge_color'         => 'rose',
                'counts_as_workday'   => false,
                'requires_attachment' => false,
                'is_system'           => true,
                'is_active'           => true,
            ],
            [
                'code'                => 'OVT',
                'name'                => 'Lembur (Overtime)',
                'type'                => 'overtime',
                'badge_color'         => 'indigo',
                'counts_as_workday'   => true,
                'requires_attachment' => false,
                'is_system'           => true,
                'is_active'           => true,
            ],
        ];

        foreach ($statuses as $st) {
            AttendanceStatus::updateOrCreate(
                ['code' => $st['code']],
                $st
            );
        }

        // Create initial standard shifts if none exist
        if (Shift::count() === 0) {
            Shift::create([
                'code'                   => 'PAGI',
                'name'                   => 'Shift Pagi',
                'description'            => 'Shift operasional pagi s/d sore',
                'start_time'             => '08:00:00',
                'end_time'               => '17:00:00',
                'late_tolerance_minutes' => 15,
                'is_overnight'           => false,
                'color'                  => '#006c49',
                'is_active'              => true,
            ]);

            Shift::create([
                'code'                   => 'SIANG',
                'name'                   => 'Shift Siang / Closing',
                'description'            => 'Shift operasional siang s/d malam',
                'start_time'             => '13:00:00',
                'end_time'               => '22:00:00',
                'late_tolerance_minutes' => 15,
                'is_overnight'           => false,
                'color'                  => '#0284c7',
                'is_active'              => true,
            ]);
        } else {
            // Update existing shifts to have codes & colors if missing
            foreach (Shift::all() as $s) {
                if (empty($s->code)) {
                    $code = str_contains(strtolower($s->name), 'malam') ? 'MALAM' : (str_contains(strtolower($s->name), 'siang') ? 'SIANG' : 'PAGI');
                    $color = str_contains(strtolower($s->name), 'malam') ? '#6366f1' : (str_contains(strtolower($s->name), 'siang') ? '#0284c7' : '#006c49');
                    $s->update(['code' => $code, 'color' => $color]);
                }
            }
        }

        // Create standard Shift Patterns if none exist
        if (\App\Models\ShiftPattern::count() === 0) {
            $shiftPagi = Shift::where('code', 'PAGI')->orWhere('name', 'like', '%Pagi%')->first();
            $shiftMalam = Shift::where('code', 'MALAM')->orWhere('code', 'SIANG')->orWhere('name', 'like', '%Malam%')->first();

            if ($shiftPagi) {
                // Pola 6-1 Shift Pagi (6 Hari Kerja, 1 Hari Libur)
                $patPagi = \App\Models\ShiftPattern::create([
                    'name'        => 'Pola 6-1 Shift Pagi (6 Kerja, 1 Libur)',
                    'description' => 'Rotasi 7 hari: 6 hari shift pagi dan 1 hari libur (OFF)',
                    'cycle_days'  => 7,
                    'is_active'   => true,
                ]);
                for ($d = 1; $d <= 6; $d++) {
                    $patPagi->items()->create(['day_number' => $d, 'shift_id' => $shiftPagi->id, 'is_off' => false]);
                }
                $patPagi->items()->create(['day_number' => 7, 'shift_id' => null, 'is_off' => true]);

                // Pola 5-2 Reguler (5 Kerja, 2 Libur)
                $pat52 = \App\Models\ShiftPattern::create([
                    'name'        => 'Pola 5-2 Reguler (5 Kerja, 2 Libur)',
                    'description' => 'Rotasi 7 hari: 5 hari shift pagi dan 2 hari libur (OFF)',
                    'cycle_days'  => 7,
                    'is_active'   => true,
                ]);
                for ($d = 1; $d <= 5; $d++) {
                    $pat52->items()->create(['day_number' => $d, 'shift_id' => $shiftPagi->id, 'is_off' => false]);
                }
                $pat52->items()->create(['day_number' => 6, 'shift_id' => null, 'is_off' => true]);
                $pat52->items()->create(['day_number' => 7, 'shift_id' => null, 'is_off' => true]);
            }

            if ($shiftMalam) {
                // Pola 6-1 Shift Malam (6 Hari Kerja, 1 Hari Libur)
                $patMalam = \App\Models\ShiftPattern::create([
                    'name'        => 'Pola 6-1 Shift Malam (6 Kerja, 1 Libur)',
                    'description' => 'Rotasi 7 hari: 6 hari shift malam dan 1 hari libur (OFF)',
                    'cycle_days'  => 7,
                    'is_active'   => true,
                ]);
                for ($d = 1; $d <= 6; $d++) {
                    $patMalam->items()->create(['day_number' => $d, 'shift_id' => $shiftMalam->id, 'is_off' => false]);
                }
                $patMalam->items()->create(['day_number' => 7, 'shift_id' => null, 'is_off' => true]);
            }
        }
    }
}
