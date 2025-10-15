<x-filament::page>
    <div class="space-y-6">
        @if (empty($summary))
            <x-filament::card>
                <div class="text-center text-sm text-gray-500">
                    Data penggajian belum tersedia untuk ditampilkan.
                </div>
            </x-filament::card>
        @else
            <x-filament::card>
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ $summary['period']['label'] }}
                        </h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Periode: {{ $summary['period']['range'] }}
                        </p>
                    </div>
                </div>
            </x-filament::card>

            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <x-filament::card>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Total Biaya Gaji</div>
                    <div class="mt-2 text-2xl font-semibold text-primary-600 dark:text-primary-400">
                        Rp {{ number_format($summary['totals']['total_salary'], 0, ',', '.') }}
                    </div>
                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Menggambarkan total gaji bersih yang dibayarkan.
                    </div>
                </x-filament::card>

                <x-filament::card>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Penghasilan Bruto</div>
                    <div class="mt-2 text-2xl font-semibold text-emerald-600 dark:text-emerald-400">
                        Rp {{ number_format($summary['totals']['gross_income'], 0, ',', '.') }}
                    </div>
                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Sebelum potongan pajak dan lainnya.
                    </div>
                </x-filament::card>

                <x-filament::card>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Total Potongan</div>
                    <div class="mt-2 text-2xl font-semibold text-rose-600 dark:text-rose-400">
                        Rp {{ number_format($summary['totals']['total_deductions'], 0, ',', '.') }}
                    </div>
                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Termasuk pajak, keterlambatan, dan lainnya.
                    </div>
                </x-filament::card>

                <x-filament::card>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Rata-rata Gaji Bersih</div>
                    <div class="mt-2 text-2xl font-semibold text-sky-600 dark:text-sky-400">
                        Rp {{ number_format($summary['totals']['average_salary'], 0, ',', '.') }}
                    </div>
                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Dari {{ $summary['totals']['employee_count'] }} karyawan.
                    </div>
                </x-filament::card>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <x-filament::card>
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Total Tunjangan</div>
                            <div class="mt-2 text-2xl font-semibold text-amber-600 dark:text-amber-400">
                                Rp {{ number_format($summary['totals']['total_allowances'], 0, ',', '.') }}
                            </div>
                        </div>
                        <x-heroicon-o-gift class="h-10 w-10 text-amber-500 dark:text-amber-300" />
                    </div>
                </x-filament::card>

                <x-filament::card>
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Total Lembur</div>
                            <div class="mt-2 text-2xl font-semibold text-indigo-600 dark:text-indigo-400">
                                Rp {{ number_format($summary['totals']['total_overtime'], 0, ',', '.') }}
                            </div>
                        </div>
                        <x-heroicon-o-clock class="h-10 w-10 text-indigo-500 dark:text-indigo-300" />
                    </div>
                </x-filament::card>
            </div>

            <x-filament::card>
                <div class="flex flex-col gap-4">
                    <div>
                        <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                            Biaya Gaji per Departemen
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Persentase terhadap total biaya gaji periode ini.
                        </p>
                    </div>

                    <div class="space-y-3">
                        @forelse ($summary['department_breakdown'] as $department)
                            <div>
                                <div class="flex items-center justify-between text-sm font-medium text-gray-700 dark:text-gray-200">
                                    <span>{{ $department['departemen'] }}</span>
                                    <span>Rp {{ number_format($department['total_gaji'], 0, ',', '.') }}</span>
                                </div>
                                <div class="mt-1 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                                    <span>{{ $department['jumlah_karyawan'] }} karyawan</span>
                                    <span>{{ $department['persentase'] }}%</span>
                                </div>
                                <div class="mt-1 h-2 w-full rounded-full bg-gray-200 dark:bg-gray-700">
                                    <div
                                        class="h-2 rounded-full bg-primary-500"
                                        style="width: {{ min(100, max(0, $department['persentase'])) }}%;"
                                    ></div>
                                </div>
                            </div>
                        @empty
                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                Belum ada rincian departemen untuk periode ini.
                            </div>
                        @endforelse
                    </div>
                </div>
            </x-filament::card>
        @endif
    </div>
</x-filament::page>
