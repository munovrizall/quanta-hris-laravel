<x-filament::page>
    <div class="space-y-6">
        <x-filament::card>
            <form wire:submit.prevent="applyFilters" class="flex flex-col h-full">
                <div class="grid gap-4 grid-cols-4 items-end flex-grow">
                    <div class="md:col-span-2">
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-200">
                            Pilih Periode
                        </label>
                        <select wire:model="selectedPeriod"
                            class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                            <option value="">-- Pilih Periode Bulanan --</option>
                            @foreach ($periodOptions as $option)
                                <option value="{{ $option['key'] }}">{{ $option['label'] }}</option>
                            @endforeach
                        </select>
                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                            Memilih periode akan otomatis mengatur tanggal mulai dan selesai.
                        </p>
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-200">
                            Tanggal Mulai
                        </label>
                        <input type="date" lang="id" wire:model.defer="startDate"
                            class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" />
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-200">
                            Tanggal Selesai
                        </label>
                        <input type="date" lang="id" wire:model.defer="endDate"
                            class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" />
                    </div>
                </div>

                <div class="mt-auto flex justify-end gap-2 pt-4">
                    <x-filament::button color="gray" type="button" wire:click="resetFilters">
                        Reset
                    </x-filament::button>
                    <x-filament::button type="submit">
                        Terapkan
                    </x-filament::button>
                </div>
            </form>
        </x-filament::card>

        @php
            $ringkasan = $summary['ringkasan'] ?? [];
            $periodeLabel = $summary['periode']['label'] ?? null;
        @endphp

        @if (empty($records))
            <x-filament::card>
                <div class="text-center text-sm text-gray-500 dark:text-gray-400">
                    Tidak ada data absensi pada periode yang dipilih.
                </div>
            </x-filament::card>
        @else
            <x-filament::card>
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                            Rekapitulasi Absensi
                        </h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Periode: {{ $periodeLabel }}
                        </p>
                    </div>
                </div>
            </x-filament::card>

            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <x-filament::card>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Total Kehadiran</div>
                    <div class="mt-2 text-2xl font-semibold text-gray-900 dark:text-gray-100">
                        {{ $ringkasan['total_kehadiran'] ?? 0 }}
                    </div>
                </x-filament::card>

                <x-filament::card>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Kehadiran Tepat Waktu</div>
                    <div class="mt-2 text-2xl font-semibold text-emerald-600 dark:text-emerald-400">
                        {{ $ringkasan['tepat_waktu'] ?? 0 }}
                    </div>
                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        {{ number_format($ringkasan['persentase_tepat'] ?? 0, 2) }}% dari total kehadiran
                    </div>
                </x-filament::card>

                <x-filament::card>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Keterlambatan</div>
                    <div class="mt-2 text-2xl font-semibold text-warning-600 dark:text-warning-400">
                        {{ $ringkasan['telat'] ?? 0 }}
                    </div>
                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        {{ number_format($ringkasan['persentase_telat'] ?? 0, 2) }}% dari total kehadiran
                    </div>
                </x-filament::card>

                <x-filament::card>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Pulang Cepat</div>
                    <div class="mt-2 text-2xl font-semibold text-rose-600 dark:text-rose-400">
                        {{ $ringkasan['pulang_cepat'] ?? 0 }}
                    </div>
                </x-filament::card>
            </div>

            <x-filament::card>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    Karyawan
                                </th>
                                <th
                                    class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    Total Kehadiran
                                </th>
                                <th
                                    class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    Tepat Waktu
                                </th>
                                <th
                                    class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    Terlambat
                                </th>
                                <th
                                    class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    Tidak Tepat
                                </th>
                                <th
                                    class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    Alfa
                                </th>
                                <th
                                    class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    Pulang Cepat
                                </th>
                                <th
                                    class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    % Tepat Waktu
                                </th>
                                <th
                                    class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    Lembur
                                </th>
                                <th
                                    class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    Cuti
                                </th>
                                <th
                                    class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    Izin
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                            @foreach ($records as $record)
                                <tr>
                                    <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">
                                        <div class="font-semibold">{{ $record['nama'] }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $record['jabatan'] }} • {{ $record['departemen'] }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-center text-sm text-gray-900 dark:text-gray-100">
                                        {{ $record['total_kehadiran'] }}
                                    </td>
                                    <td class="px-4 py-3 text-center text-sm text-emerald-600 dark:text-emerald-400">
                                        {{ $record['tepat_waktu'] }}
                                    </td>
                                    <td class="px-4 py-3 text-center text-sm text-warning-600 dark:text-warning-400">
                                        {{ $record['terlambat'] }}
                                    </td>
                                    <td class="px-4 py-3 text-center text-sm text-gray-900 dark:text-gray-100">
                                        {{ $record['tidak_tepat'] }}
                                    </td>
                                    <td class="px-4 py-3 text-center text-sm text-rose-600 dark:text-rose-400">
                                        {{ $record['alfa'] }}
                                    </td>
                                    <td class="px-4 py-3 text-center text-sm text-rose-600 dark:text-rose-400">
                                        {{ $record['pulang_cepat'] }}
                                    </td>
                                    <td class="px-4 py-3 text-center text-sm text-gray-900 dark:text-gray-100">
                                        {{ number_format($record['persentase_tepat'], 2) }}%
                                    </td>
                                    <td class="px-4 py-3 text-center text-sm text-emerald-600 dark:text-emerald-400">
                                        {{ $record['lembur_disetujui'] }}
                                    </td>
                                    <td class="px-4 py-3 text-center text-sm text-sky-600 dark:text-sky-400">
                                        {{ $record['cuti_disetujui'] }}
                                    </td>
                                    <td class="px-4 py-3 text-center text-sm text-indigo-600 dark:text-indigo-400">
                                        {{ $record['izin_disetujui'] }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-filament::card>
        @endif
    </div>
</x-filament::page>