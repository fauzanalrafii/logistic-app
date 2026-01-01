<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StatusSeeder extends Seeder
{
    public function run()
    {
        $data = [
            // PROJECT STATUS
            ['no' => 1, 'name' => 'PLAN ON DESK', 'type' => 'project', 'description' => 'Perencanaan proyek awal'],
            ['no' => 2, 'name' => 'SPK SURVEY', 'type' => 'project', 'description' => 'Proses generate SPK Survey'],
            ['no' => 3, 'name' => 'SURVEY DRM', 'type' => 'project', 'description' => 'Proses pembuatan dan validasi DRM'],
            ['no' => 4, 'name' => 'IMPLEMENTASI', 'type' => 'project', 'description' => 'Proses implementasi setelah survey DRM'],
            ['no' => 5, 'name' => 'KONSTRUKSI', 'type' => 'project', 'description' => 'Konstruksi sedang berlangsung'],
            ['no' => 6, 'name' => 'GO LIVE', 'type' => 'project', 'description' => 'Layanan sudah aktif'],
            ['no' => 7, 'name' => 'HANDOVER', 'type' => 'project', 'description' => 'Serah terima ke operasional'],
            ['no' => 8, 'name' => 'CLOSED', 'type' => 'project', 'description' => 'Proyek selesai dan ditutup'],

            // DOCUMENT STATUS
            ['no' => 1, 'name' => 'DRAFT', 'type' => 'document', 'description' => 'Dokumen masih disusun'],
            ['no' => 2, 'name' => 'VERIFIED', 'type' => 'document', 'description' => 'Sudah diverifikasi'],
            ['no' => 3, 'name' => 'FINAL', 'type' => 'document', 'description' => 'Dokumen final'],

            // PLANNING STATUS
            ['no' => 1, 'name' => 'DRAFT', 'type' => 'planning', 'description' => 'Planning masih dalam draft'],
            ['no' => 2, 'name' => 'SUBMITTED', 'type' => 'planning', 'description' => 'Planning sudah di-submit'],

            // APPROVAL INSTANCE STATUS
            ['no' => 1, 'name' => 'PENDING', 'type' => 'approval', 'description' => 'Menunggu approval'],
            ['no' => 2, 'name' => 'APPROVED', 'type' => 'approval', 'description' => 'Disetujui'],
            ['no' => 3, 'name' => 'REJECTED', 'type' => 'approval', 'description' => 'Ditolak'],

            // SPK SURVEY STATUS
            ['no' => 1, 'name' => 'DRAFT', 'type' => 'spk', 'description' => 'SPK Survey masih dalam draft'],
            ['no' => 2, 'name' => 'ISSUED', 'type' => 'spk', 'description' => 'SPK Survey sudah di terbitkan'],
        ];

        DB::table('vl_status')->upsert(
            $data,
            ['name', 'type'],      // unique key pair → kalau name+type ada: update
            ['no', 'description']  // kolom yang akan di-update jika sudah ada
        );
    }
}
