<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PrintRoutesRequireAuthTest extends TestCase
{
    public static function printRoutes(): array
    {
        return [
            'slip gaji semua' => ['slip-gaji.cetak', ['tahun' => 2026, 'bulan' => 9]],
            'slip gaji individual' => ['slip-gaji.cetak-individual', ['karyawan_id' => 'K0001', 'tahun' => 2026, 'bulan' => 9]],
            'laporan keuangan' => ['laporan-keuangan.cetak', ['tahun' => 2026, 'bulan' => 9]],
            'laporan kinerja' => ['laporan-kinerja.cetak', ['tahun' => 2026, 'bulan' => 9]],
            'rekapitulasi absensi' => ['rekapitulasi-absensi.pdf', ['start_date' => '2026-09-01', 'end_date' => '2026-09-30']],
        ];
    }

    #[DataProvider('printRoutes')]
    public function test_guest_is_redirected_to_login(string $name, array $params): void
    {
        $this->get(route($name, $params))
            ->assertRedirect(route('filament.admin.auth.login'));
    }
}
