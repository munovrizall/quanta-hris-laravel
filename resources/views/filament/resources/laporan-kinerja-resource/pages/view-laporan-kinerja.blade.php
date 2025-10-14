<x-filament::page>
    <div class="space-y-6">
        @if (empty($summary))
            <x-filament::card>
                <div class="text-center text-sm text-gray-500">
                    Data kinerja belum tersedia untuk ditampilkan.
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
                    <div class="text-sm text-gray-500 dark:text-gray-400">Kehadiran Tepat Waktu</div>
                    <div class="mt-2 text-2xl font-semibold ">
                        {{ number_format($summary['attendance']['on_time_rate'], 2) }}%
                    </div>
                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        {{ $summary['attendance']['on_time'] }} dari {{ $summary['attendance']['total'] }} kehadiran
                    </div>
                </x-filament::card>

                <x-filament::card>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Keterlambatan</div>
                    <div class="mt-2 text-2xl font-semibold text-warning-600 dark:text-warning-400">
                        {{ $summary['attendance']['late'] }}
                    </div>
                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        {{ number_format($summary['attendance']['late_rate'], 2) }}% dari total kehadiran
                    </div>
                </x-filament::card>

                <x-filament::card>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Pulang Cepat</div>
                    <div class="mt-2 text-2xl font-semibold text-rose-600 dark:text-rose-400">
                        {{ $summary['attendance']['early_leave'] }} Karyawan
                    </div>

                </x-filament::card>

                <x-filament::card>
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Sesi Lembur</div>
                            <div class="mt-2 text-2xl font-semibold text-emerald-600 dark:text-emerald-400">
                                {{ $summary['lembur']['hours'] }} Jam
                            </div>
                        </div>
                        <x-heroicon-o-clock class="h-10 w-10 text-emerald-500 dark:text-emerald-300" />
                    </div>
                </x-filament::card>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <x-filament::card>
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Pengajuan Cuti</div>
                            <div class="mt-2 text-2xl font-semibold text-sky-600 dark:text-sky-400">
                                {{ $summary['cuti']['requests'] }} Pengajuan
                            </div>
                        </div>
                        <x-heroicon-o-calendar-days class="h-10 w-10 text-sky-500 dark:text-sky-300" />
                    </div>
                </x-filament::card>

                <x-filament::card>
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Pengajuan Izin</div>
                            <div class="mt-2 text-2xl font-semibold text-indigo-600 dark:text-indigo-400">
                                {{ $summary['izin']['requests'] }} Pengajuan
                            </div>
                        </div>
                        <x-heroicon-o-clipboard-document-check class="h-10 w-10 text-indigo-500 dark:text-indigo-300" />
                    </div>
                </x-filament::card>
            </div>
        @endif
    </div>

</x-filament::page>