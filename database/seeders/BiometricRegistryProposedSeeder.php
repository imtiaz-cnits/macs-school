<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BiometricDeviceUser;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Support\Facades\App;
use RuntimeException;

/**
 * BiometricRegistryProposedSeeder
 * 
 * PROPOSED BLUEPRINT SEEDER for MACS School ZKTeco K60 Terminal Integration.
 * 
 * PURPOSE:
 * Reconciles and maps the physical K60 hardware state (546 device users, 7,266 logs)
 * with the MACS production database baseline into the centralized `biometric_device_users` registry.
 * 
 * SAFETY DIRECTIVES:
 * - DO NOT run directly against the live production database without prior review and explicit authorization.
 * - Production execution requires APP_FORCE_REGISTRY_SEED=true environment flag.
 * - Preserves existing device UIDs 31..577 for students and dedicated slots for staff.
 * - Explicitly reserves Teacher #5 on UID 569, preventing student collision.
 * - Explicitly marks Fahim (UID 10025, PIN 21) as 'reserved' and UNRESOLVED.
 * - Explicitly retires test profiles (UID 217, 218).
 */
class BiometricRegistryProposedSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Strict Production Safety Guard (No bypass permitted)
        if (App::environment('production')) {
            throw new RuntimeException(
                "CRITICAL SECURITY BLOCK: BiometricRegistryProposedSeeder is a local development blueprint ONLY. " .
                "Direct seeder execution against production database is permanently prohibited to prevent unverified biometric mapping."
            );
        }

        $deviceId = 'k60_main';

        $this->command?->info("Starting proposed biometric device registry seeding for device: {$deviceId}...");

        // 2. Confirmed Staff & Teachers (Phase 3C Audit Inventory)
        $confirmedStaff = [
            // Teacher #5: Imtiaz Ahmed (Physical UID/PIN 569, Card 0016001686, Finger 6)
            [
                'device_id'          => $deviceId,
                'device_uid'         => 569,
                'device_userid'      => '569',
                'person_type'        => 'teacher',
                'person_id'          => 5,
                'card_number'        => '0016001686',
                'has_fingerprint'    => true,
                'fingerprint_count'  => 1,
                'biometric_checksum' => '1c46ce072f70fd38be770e47156514d5825b6f45fa91714575e6a51766fa9410',
                'status'             => 'active',
                'verified_at'        => now(),
                'notes'              => 'Phase 3C Audit: Imtiaz Ahmed (Teacher #5). Enrolled directly on K60 at UID 569 with Finger 6. (DB biometric_id was previously 1).',
            ],
            // Teacher #6: Md Mahmudul Hasan (Physical UID/PIN 10006, Card 0016008688, Finger 6)
            [
                'device_id'          => $deviceId,
                'device_uid'         => 10006,
                'device_userid'      => '10006',
                'person_type'        => 'teacher',
                'person_id'          => 6,
                'card_number'        => '0016008688',
                'has_fingerprint'    => true,
                'fingerprint_count'  => 1,
                'biometric_checksum' => 'b087883ec7e9ba7a76f790fb96f915c46ca4c9aaa5bf876077189e953eedcb80',
                'status'             => 'active',
                'verified_at'        => now(),
                'notes'              => 'Phase 3C Audit: Md Mahmudul Hasan (Teacher #6). Enrolled on K60 at UID 10006 with Finger 6.',
            ],
            // Teacher #8: FAHIM AHMAD ZAMIL (Physical UID/PIN 10008, Card 0016002016, Finger 6)
            [
                'device_id'          => $deviceId,
                'device_uid'         => 10008,
                'device_userid'      => '10008',
                'person_type'        => 'teacher',
                'person_id'          => 8,
                'card_number'        => '0016002016',
                'has_fingerprint'    => true,
                'fingerprint_count'  => 1,
                'biometric_checksum' => 'ae7d31b6438f277e105cef39c5537c0843be87b102691e7764b8bf3291c55c41',
                'status'             => 'active',
                'verified_at'        => now(),
                'notes'              => 'Phase 3C Audit: FAHIM AHMAD ZAMIL (Teacher #8). Enrolled on K60 at UID 10008 with Finger 6.',
            ],
            // Teacher #10: Md. Nurul Islam (Physical UID/PIN 10010, Card 0016008447, No Fingerprint)
            [
                'device_id'          => $deviceId,
                'device_uid'         => 10010,
                'device_userid'      => '10010',
                'person_type'        => 'teacher',
                'person_id'          => 10,
                'card_number'        => '0016008447',
                'has_fingerprint'    => false,
                'fingerprint_count'  => 0,
                'biometric_checksum' => null,
                'status'             => 'active',
                'verified_at'        => now(),
                'notes'              => 'Phase 3C Audit: Md. Nurul Islam (Teacher #10). Principal/Admin. RFID card enrolled on K60.',
            ],
        ];

        foreach ($confirmedStaff as $staff) {
            BiometricDeviceUser::updateOrCreate(
                ['device_id' => $staff['device_id'], 'device_uid' => $staff['device_uid']],
                $staff
            );
        }

        // 3. Unresolved Legacy Staff Identity (Fahim: UID 10025, PIN 21)
        // CRITICAL: DO NOT merge with Teacher #8 (Fahim Ahmad Zamil).
        // Uses nullable person_id (null) and status 'unresolved' to avoid false relationships.
        BiometricDeviceUser::updateOrCreate(
            ['device_id' => $deviceId, 'device_uid' => 10025],
            [
                'device_id'          => $deviceId,
                'device_uid'         => 10025,
                'device_userid'      => '21',
                'person_type'        => 'staff',
                'person_id'          => null, // Explicitly unlinked/unresolved
                'card_number'        => '0016008647',
                'has_fingerprint'    => true,
                'fingerprint_count'  => 2, // Finger 3 and Finger 6
                'biometric_checksum' => '2b7455cbdc1e646096c1a83435cf348b01a1907d3181765cc8709c3ea48f7274',
                'status'             => 'unresolved',
                'verified_at'        => null,
                'notes'              => 'UNRESOLVED IDENTITY (Phase 3C Audit): 16 historical punches on K60 under PIN 21. Name on device: "Fahim". Enrolled with Finger 3 & Finger 6. Candidate legacy profile for FAHIM AHMAD ZAMIL (UID 10008), but held in unresolved status with null person_id pending physical field confirmation.',
            ]
        );

        // 4. Retired Test Profiles on K60 Terminal (UID 217, 218)
        $retiredProfiles = [
            [
                'device_id'          => $deviceId,
                'device_uid'         => 217,
                'device_userid'      => '217',
                'person_type'        => 'student',
                'person_id'          => null, // Nullable to avoid synthetic ID 0
                'card_number'        => null,
                'has_fingerprint'    => false,
                'fingerprint_count'  => 0,
                'biometric_checksum' => null,
                'status'             => 'retired',
                'verified_at'        => null,
                'notes'              => 'RETIRED PROFILE (Phase 3C Audit): Hardware record "Dreamy faith symbol" on K60. Retired to prevent UID reassignment.',
            ],
            [
                'device_id'          => $deviceId,
                'device_uid'         => 218,
                'device_userid'      => '218',
                'person_type'        => 'student',
                'person_id'          => null, // Nullable to avoid synthetic ID 0
                'card_number'        => null,
                'has_fingerprint'    => false,
                'fingerprint_count'  => 0,
                'biometric_checksum' => null,
                'status'             => 'retired',
                'verified_at'        => null,
                'notes'              => 'RETIRED PROFILE (Phase 3C Audit): Hardware record "Ahnaf Islam Abir" on K60. Retired to prevent UID reassignment.',
            ],
        ];

        foreach ($retiredProfiles as $retired) {
            BiometricDeviceUser::updateOrCreate(
                ['device_id' => $retired['device_id'], 'device_uid' => $retired['device_uid']],
                $retired
            );
        }

        // 5. Active Students (Mapping IDs 31..577, skipping reserved 569)
        $students = Student::orderBy('id')->get();
        $seededStudents = 0;

        foreach ($students as $student) {
            $studentId = (int) $student->id;

            // Strict collision guard: Student ID 569 is reserved for Teacher #5
            if ($studentId === 569) {
                $this->command?->warn("Skipping Student ID 569: Reserved for staff biometric identity.");
                continue;
            }

            BiometricDeviceUser::updateOrCreate(
                ['device_id' => $deviceId, 'device_uid' => $studentId],
                [
                    'device_id'          => $deviceId,
                    'device_uid'         => $studentId,
                    'device_userid'      => (string) $studentId,
                    'person_type'        => 'student',
                    'person_id'          => $studentId,
                    'card_number'        => !empty($student->card_number) ? $student->card_number : null,
                    'has_fingerprint'    => false,
                    'fingerprint_count'  => 0,
                    'biometric_checksum' => null,
                    'status'             => 'active',
                    'verified_at'        => now(),
                    'notes'              => "Mapped from database student record ID {$studentId} ({$student->student_name}).",
                ]
            );

            $seededStudents++;
        }

        $this->command?->info("Biometric device registry blueprint seeded successfully! (Students: {$seededStudents}, Staff: 4, Unresolved: 1, Retired: 2)");
    }
}

