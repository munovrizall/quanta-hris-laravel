<?php

namespace App\Filament\Resources\PenggajianResource\Pages;

use App\Filament\Resources\PenggajianResource;
use App\Filament\Resources\PenggajianResource\Actions\EditGajiKaryawanAction;
use App\Models\Karyawan;
use App\Models\Penggajian;
use App\Services\AbsensiService;
use App\Services\TunjanganService;
use App\Services\BpjsService;
use App\Services\LemburService;
use App\Services\Pph21Service;
use App\Services\PotonganService;
use App\Utils\MonthHelper;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Forms;
use Filament\Notifications\Notification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ViewPenggajian extends ViewRecord
{
  protected static string $resource = PenggajianResource::class;

  protected static ?string $title = 'Detail Penggajian';

  protected static ?string $breadcrumb = 'Detail';

  protected $listeners = [
    'editKaryawan' => 'openEditModal',
    'markTransfer' => 'markKaryawanTransfer',
  ];

  public int $currentPage = 1;

  public string $paginationPath = '';

  // Remove constructor and use lazy loading instead
  private ?TunjanganService $tunjanganService = null;
  private ?BpjsService $bpjsService = null;
  private ?LemburService $lemburService = null;
  private ?Pph21Service $pph21Service = null;
  private ?PotonganService $potonganService = null;

  // Lazy loading methods for services
  private function getTunjanganService(): TunjanganService
  {
    if ($this->tunjanganService === null) {
      $this->tunjanganService = new TunjanganService();
    }
    return $this->tunjanganService;
  }

  private function getBpjsService(): BpjsService
  {
    if ($this->bpjsService === null) {
      $this->bpjsService = new BpjsService();
    }
    return $this->bpjsService;
  }

  private function getLemburService(): LemburService
  {
    if ($this->lemburService === null) {
      $this->lemburService = new LemburService();
    }
    return $this->lemburService;
  }

  private function getPph21Service(): Pph21Service
  {
    if ($this->pph21Service === null) {
      $this->pph21Service = new Pph21Service();
    }
    return $this->pph21Service;
  }

  private function getPotonganService(): PotonganService
  {
    if ($this->potonganService === null) {
      $this->potonganService = new PotonganService();
    }
    return $this->potonganService;
  }

  public function getColumnSpan(): int|string|array
  {
    return 'full';
  }

  public function getColumnStart(): int|string|array
  {
    return '1';
  }

  public function mount(int|string $record = null): void
  {
    $tahun = request()->route('tahun');
    $bulan = request()->route('bulan');

    if ($tahun && $bulan) {
      $penggajian = Penggajian::where('periode_bulan', $bulan)
        ->where('periode_tahun', $tahun)
        ->first();

      if (!$penggajian) {
        abort(404, 'Penggajian tidak ditemukan untuk periode tersebut');
      }

      $this->record = $penggajian;
    } else {
      parent::mount($record);
    }

    $this->currentPage = max(1, request()->integer('page', $this->currentPage));
    $this->paginationPath = request()->fullUrlWithoutQuery('page');
  }

  public function resolveRecord(int|string $key): \Illuminate\Database\Eloquent\Model
  {
    if (isset($this->record)) {
      return $this->record;
    }

    return parent::resolveRecord($key);
  }

  protected function getHeaderActions(): array
  {
    return [
      $this->ajukanDrafHeaderAction(),
      $this->verifikasiDrafHeaderAction(),
      $this->setujuiDrafHeaderAction(),
      $this->sudahTransferDrafHeaderAction(),
      $this->tolakDrafHeaderAction(),
      Actions\DeleteAction::make()
        ->label('Hapus')
        ->requiresConfirmation()
        ->modalHeading('Hapus Penggajian')
        ->modalDescription('Apakah Anda yakin ingin menghapus penggajian ini?')
        ->modalSubmitActionLabel('Ya, hapus')
        ->action(function () {
          Penggajian::where('periode_bulan', $this->record->periode_bulan)
            ->where('periode_tahun', $this->record->periode_tahun)
            ->delete();

          return redirect(static::getResource()::getUrl('index'));
        })
        ->visible(function () {
          $user = Auth::user();
          return $user &&
            $user->role_id === 'R02' &&
            $this->record->status_penggajian === 'Draf';
        })
        ->successRedirectUrl(static::getResource()::getUrl('index')),
    ];
  }

  private function ajukanDrafHeaderAction()
  {
    return Actions\Action::make('ajukan_draf_header')
      ->label('Ajukan Draf')
      ->icon('heroicon-o-check-circle')
      ->color('success')
      ->size('sm')
      ->action(function () {
        try {
          // Update all records for this period
          $updated = Penggajian::where('periode_bulan', $this->record->periode_bulan)
            ->where('periode_tahun', $this->record->periode_tahun)
            ->where('status_penggajian', 'Draf')
            ->orWhere('status_penggajian', 'Ditolak')
            ->update([
              'status_penggajian' => 'Diajukan',
              'updated_at' => now(),
            ]);

          if ($updated > 0) {
            $this->notifyManagersPenggajianSubmitted($updated);

            Notification::make()
              ->title('Pengajuan Berhasil!')
              ->body("Draf penggajian berhasil diajukan. {$updated} record telah diperbarui ke status 'Diajukan'.")
              ->success()
              ->duration(5000)
              ->send();

            // Simply reload the page via JavaScript
            $this->js('window.location.reload()');
          } else {
            Notification::make()
              ->title('Tidak Ada Perubahan')
              ->body('Tidak ada record dengan status Draf yang dapat diajukan.')
              ->warning()
              ->send();
          }
        } catch (\Exception $e) {
          Log::error('Error updating penggajian status: ' . $e->getMessage());

          Notification::make()
            ->title('Terjadi Kesalahan')
            ->body('Gagal mengajukan draf penggajian. Silakan coba lagi.')
            ->danger()
            ->send();
        }
      })
      ->visible(function () {
        $user = Auth::user();
        return $user &&
          $user->role_id === 'R02' && (
          $this->record->status_penggajian === 'Draf' ||
          $this->record->status_penggajian === 'Ditolak'
        );
      });
  }

  private function notifyManagersPenggajianSubmitted(int $recordsUpdated): void
  {
    $user = Auth::user();

    if (!$user || $user->role_id !== 'R02' || !$this->record) {
      return;
    }

    $periode = $this->getPeriodeLabel();
    $submittedBy = $user->nama_lengkap ?? $user->name ?? 'Staff HR';

    $message = sprintf(
      '%s mengajukan draf penggajian periode %s (total %d data).',
      $submittedBy,
      $periode,
      $recordsUpdated
    );

    $this->sendNotificationToRole(
      roleId: 'R03',
      title: 'Pengajuan Draf Penggajian',
      body: $message,
      icon: 'heroicon-o-paper-airplane',
      iconColor: 'primary',
      color: 'info'
    );
  }

  private function notifyFinanceManagersPenggajianVerified(int $recordsUpdated): void
  {
    $user = Auth::user();

    if (!$user || $user->role_id !== 'R03' || !$this->record) {
      return;
    }

    Log::info('notifyFinanceManagersPenggajianVerified called.', [
      'records_updated' => $recordsUpdated,
      'user_id' => $user->getKey(),
    ]);

    $periode = $this->getPeriodeLabel();
    $verifiedBy = $user->nama_lengkap ?? $user->name ?? 'Manager HR';

    $message = sprintf(
      '%s memverifikasi draf penggajian periode %s (%d data) dan menunggu persetujuan Anda.',
      $verifiedBy,
      $periode,
      $recordsUpdated
    );

    $this->sendNotificationToRole(
      roleId: 'R04',
      title: 'Penggajian Siap Disetujui',
      body: $message,
      icon: 'heroicon-o-clipboard-document-check',
      iconColor: 'warning',
      color: 'warning'
    );
  }

  private function notifyAccountPaymentsPenggajianApproved(int $recordsUpdated): void
  {
    $user = Auth::user();

    if (!$user || $user->role_id !== 'R04' || !$this->record) {
      return;
    }

    Log::info('notifyAccountPaymentsPenggajianApproved called.', [
      'records_updated' => $recordsUpdated,
      'user_id' => $user->getKey(),
    ]);

    $periode = $this->getPeriodeLabel();
    $approvedBy = $user->nama_lengkap ?? $user->name ?? 'Manager Finance';

    $message = sprintf(
      '%s menyetujui draf penggajian periode %s (%d data). Silakan proses transfer.',
      $approvedBy,
      $periode,
      $recordsUpdated
    );

    $this->sendNotificationToRole(
      roleId: 'R05',
      title: 'Penggajian Siap Ditransfer',
      body: $message,
      icon: 'heroicon-o-banknotes',
      iconColor: 'success',
      color: 'success'
    );
  }

  private function notifyStaffPenggajianRejected(int $recordsUpdated, string $reason): void
  {
    $user = Auth::user();

    if (
      !$user ||
      !in_array($user->role_id, ['R03', 'R04'], true) ||
      !$this->record
    ) {
      return;
    }

    Log::info('notifyStaffPenggajianRejected called.', [
      'records_updated' => $recordsUpdated,
      'user_id' => $user->getKey(),
    ]);

    $periode = $this->getPeriodeLabel();
    $rejectedBy = $user->nama_lengkap ?? $user->name ?? 'Atasan';

    $message = sprintf(
      '%s menolak draf penggajian periode %s (%d data). Alasan: %s',
      $rejectedBy,
      $periode,
      $recordsUpdated,
      Str::limit($reason, 120)
    );

    $this->sendNotificationToRole(
      roleId: 'R02',
      title: 'Penggajian Ditolak',
      body: $message,
      icon: 'heroicon-o-x-circle',
      iconColor: 'danger',
      color: 'danger'
    );
  }

  private function sendNotificationToRole(
    string $roleId,
    string $title,
    string $body,
    string $icon = 'heroicon-o-bell',
    string $iconColor = 'primary',
    string $color = 'info'
  ): void {
    $recipients = Karyawan::query()
      ->where('role_id', $roleId)
      ->get();

    if ($recipients->isEmpty()) {
      Log::warning('Penggajian notification skipped: no recipients found.', [
        'role_id' => $roleId,
        'title' => $title,
      ]);
      return;
    }

    try {
      $notification = Notification::make()
        ->title($title)
        ->body($body)
        ->icon($icon)
        ->iconColor($iconColor)
        ->color($color);

      $notification->sendToDatabase($recipients);

      Log::info('Penggajian notification dispatched.', [
        'role_id' => $roleId,
        'title' => $title,
        'recipient_ids' => $recipients->pluck('karyawan_id')->all(),
        'recipient_count' => $recipients->count(),
      ]);
    } catch (\Throwable $exception) {
      Log::error('Failed to send penggajian notification.', [
        'role_id' => $roleId,
        'title' => $title,
        'error' => $exception->getMessage(),
      ]);
    }
  }

  private function getPeriodeLabel(): string
  {
    return MonthHelper::formatPeriod(
      $this->record->periode_bulan,
      $this->record->periode_tahun
    );
  }

  private function verifikasiDrafHeaderAction()
  {
    return Actions\Action::make('verifikasi_draf_header')
      ->label('Verifikasi Draf')
      ->icon('heroicon-o-check-circle')
      ->color('success')
      ->size('sm')
      ->action(function () {
        try {
          // Update all records for this period
          $updated = Penggajian::where('periode_bulan', $this->record->periode_bulan)
            ->where('periode_tahun', $this->record->periode_tahun)
            ->where('status_penggajian', 'Diajukan')
            ->update([
              'status_penggajian' => 'Diverifikasi',
              'updated_at' => now(),
            ]);

          if ($updated > 0) {
            $this->notifyFinanceManagersPenggajianVerified($updated);

            Notification::make()
              ->title('Verifikasi Berhasil!')
              ->body("Draf penggajian berhasil diverifikasi. {$updated} record telah diperbarui ke status 'Diverifikasi'.")
              ->success()
              ->duration(5000)
              ->send();

            // Simply reload the page via JavaScript
            $this->js('window.location.reload()');
          } else {
            Notification::make()
              ->title('Tidak Ada Perubahan')
              ->body('Tidak ada record dengan status Diajukan yang dapat diverifikasi.')
              ->warning()
              ->send();
          }
        } catch (\Exception $e) {
          Log::error('Error updating penggajian status: ' . $e->getMessage());

          Notification::make()
            ->title('Terjadi Kesalahan')
            ->body('Gagal memverifikasi draf penggajian. Silakan coba lagi.')
            ->danger()
            ->send();
        }
      })
      ->visible(function () {
        $user = Auth::user();
        return $user &&
          $user->role_id === 'R03' &&
          $this->record->status_penggajian === 'Diajukan';
      });
  }

  private function setujuiDrafHeaderAction()
  {
    return Actions\Action::make('setujui_draf_header')
      ->label('Setujui Draf')
      ->icon('heroicon-o-check-circle')
      ->color('success')
      ->size('sm')
      ->action(function () {
        try {
          // Update all records for this period
          $updated = Penggajian::where('periode_bulan', $this->record->periode_bulan)
            ->where('periode_tahun', $this->record->periode_tahun)
            ->where('status_penggajian', 'Diverifikasi')
            ->update([
              'status_penggajian' => 'Disetujui',
              'updated_at' => now(),
            ]);

          if ($updated > 0) {
            $this->notifyAccountPaymentsPenggajianApproved($updated);

            Notification::make()
              ->title('Setujui Berhasil!')
              ->body("Draf penggajian berhasil disetujui. {$updated} record telah diperbarui ke status 'Disetujui'.")
              ->success()
              ->duration(5000)
              ->send();

            // Simply reload the page via JavaScript
            $this->js('window.location.reload()');
          } else {
            Notification::make()
              ->title('Tidak Ada Perubahan')
              ->body('Tidak ada record dengan status Diverifikasi yang dapat disetujui.')
              ->warning()
              ->send();
          }
        } catch (\Exception $e) {
          Log::error('Error updating penggajian status: ' . $e->getMessage());

          Notification::make()
            ->title('Terjadi Kesalahan')
            ->body('Gagal menyetujui draf penggajian. Silakan coba lagi.')
            ->danger()
            ->send();
        }
      })
      ->visible(function () {
        $user = Auth::user();
        return $user &&
          $user->role_id === 'R04' &&
          $this->record->status_penggajian === 'Diverifikasi';
      });
  }

  private function sudahTransferDrafHeaderAction()
  {
    return Actions\Action::make('sudah_transfer_draf_header')
      ->label('Tandai Semua Sudah Transfer')
      ->icon('heroicon-o-check-circle')
      ->color('success')
      ->size('sm')
      ->action(function () {
        try {
          // Update all records for this period
          $updated = Penggajian::where('periode_bulan', $this->record->periode_bulan)
            ->where('periode_tahun', $this->record->periode_tahun)
            ->where('status_penggajian', 'Disetujui')
            ->update([
              'sudah_ditransfer' => true,
              'updated_at' => now(),
            ]);

          if ($updated > 0) {
            Notification::make()
              ->title('Tandai Semua Sudah Transfer Berhasil!')
              ->body("Draf penggajian berhasil ditandai sebagai sudah transfer. {$updated} record telah diperbarui.")
              ->success()
              ->duration(5000)
              ->send();

            // Simply reload the page via JavaScript
            $this->js('window.location.reload()');
          } else {
            Notification::make()
              ->title('Tidak Ada Perubahan')
              ->body('Tidak ada record dengan status Disetujui yang dapat ditandai sebagai sudah transfer.')
              ->warning()
              ->send();
          }
        } catch (\Exception $e) {
          Log::error('Error updating penggajian status: ' . $e->getMessage());

          Notification::make()
            ->title('Terjadi Kesalahan')
            ->body('Gagal transfer draf penggajian. Silakan coba lagi.')
            ->danger()
            ->send();
        }
      })
      ->visible(function () {
        $user = Auth::user();
        return $user &&
          $user->role_id === 'R05' &&
          $this->record->status_penggajian === 'Disetujui' &&
          !$this->record->sudah_ditransfer;
      });
  }

  private function tolakDrafHeaderAction()
  {
    return Actions\Action::make('tolak_draf_header')
      ->label('Tolak Draf')
      ->icon('heroicon-o-x-circle')
      ->color('danger')
      ->size('sm')
      ->form([
        Forms\Components\Textarea::make('alasan_penolakan')
          ->label('Alasan Penolakan')
          ->required()
          ->rows(3)
          ->placeholder('Masukkan alasan penolakan draf penggajian...')
      ])
      ->modalSubmitActionLabel('Simpan')
      ->modalFooterActionsAlignment('right')
      ->action(function (array $data) {
        try {
          // Update all records for this period
          $updated = Penggajian::where('periode_bulan', $this->record->periode_bulan)
            ->where('periode_tahun', $this->record->periode_tahun)
            ->where(function ($query) {
            $query->where('status_penggajian', 'Diajukan')
              ->orWhere('status_penggajian', 'Diverifikasi');
          })
            ->update([
              'status_penggajian' => 'Ditolak',
              'catatan_penolakan_draf' => $data['alasan_penolakan'],
              'updated_at' => now(),
            ]);

          if ($updated > 0) {
            $this->notifyStaffPenggajianRejected($updated, $data['alasan_penolakan']);

            Notification::make()
              ->title('Pengajuan Berhasil!')
              ->body("Draf penggajian berhasil ditolak. {$updated} record telah diperbarui ke status 'Draf'.")
              ->success()
              ->duration(5000)
              ->send();

            $this->js('window.location.reload()');
          } else {
            $reason = 'Tidak ada record dengan status Diajukan atau Diverifikasi yang dapat diubah.';
            Log::warning("Gagal menolak draf penggajian untuk periode {$this->record->periode_bulan}-{$this->record->periode_tahun}. Alasan: {$reason}");

            Notification::make()
              ->title('Tidak Ada Perubahan')
              ->body('Gagal melakukan perubahan. ' . $reason)
              ->warning()
              ->send();
          }
        } catch (\Exception $e) {
          Log::error("Error updating penggajian status untuk periode {$this->record->periode_bulan}-{$this->record->periode_tahun}: " . $e->getMessage());

          Notification::make()
            ->title('Terjadi Kesalahan')
            ->body('Gagal menyetujui draf penggajian. Silakan coba lagi. Alasan: ' . $e->getMessage())
            ->danger()
            ->send();
        }
      })
      ->visible(function () {
        $user = Auth::user();
        return $user &&
          ($user->role_id === 'R03' &&
            $this->record->status_penggajian === 'Diajukan') ||
          ($user->role_id === 'R04' &&
            $this->record->status_penggajian === 'Diverifikasi');
      });
  }

  public function getBreadcrumbs(): array
  {
    $breadcrumbs = parent::getBreadcrumbs();

    if (isset($this->record)) {
      $periodeName = MonthHelper::getMonthName($this->record->periode_bulan) . ' ' . $this->record->periode_tahun;
      $breadcrumbs[array_key_last($breadcrumbs)] = $periodeName;
    }

    return $breadcrumbs;
  }

  protected function getActions(): array
  {
    return [
      $this->editKaryawanGajiAction(),
    ];
  }

  public function editKaryawanGajiAction()
  {
    return EditGajiKaryawanAction::make()
      ->visible(fn() => $this->record->status_penggajian === 'Draf' ||
        $this->record->status_penggajian === 'Ditolak');
  }

  public function markKaryawanTransfer($detailId)
  {
    $user = Auth::user();
    if (!$user || $user->role_id !== 'R05') {
      Notification::make()
        ->title('Akses Ditolak')
        ->body('Anda tidak memiliki akses untuk melakukan transfer.')
        ->danger()
        ->send();
      return;
    }

    $penggajian = Penggajian::find($detailId);
    if ($penggajian && $penggajian->status_penggajian === 'Disetujui' && !$penggajian->sudah_ditransfer) {
      $penggajian->sudah_ditransfer = true;
      $penggajian->save();

      Notification::make()
        ->title('Berhasil')
        ->body('Status transfer berhasil diperbarui.')
        ->success()
        ->send();

      $this->js('window.location.reload()');
    } else {
      Notification::make()
        ->title('Gagal')
        ->body('Data penggajian tidak valid atau sudah diproses.')
        ->danger()
        ->send();
    }
  }

  public function openEditModal($detailId)
  {
    $this->mountAction('editKaryawanGaji', ['detailId' => $detailId]);
  }

  public function infolist(Infolist $infolist): Infolist
  {
    $paginatedDetailPenggajian = $this->getPaginatedDetailPenggajianFromDatabase($this->record);
    $karyawanData = $this->processKaryawanDataFromDatabase($paginatedDetailPenggajian);

    return $infolist
      ->schema([
        Infolists\Components\Section::make('Informasi Penggajian')
          ->schema([
            Infolists\Components\TextEntry::make('periode')
              ->label('Periode')
              ->getStateUsing(function ($record): string {
                return MonthHelper::formatPeriod($record->periode_bulan, $record->periode_tahun);
              }),

            Infolists\Components\TextEntry::make('total_karyawan_from_db')
              ->label('Total Karyawan')
              ->getStateUsing(function ($record): int {
                return $this->getTotalKaryawanCountFromDatabase($record);
              })
              ->badge()
              ->color('info'),


            Infolists\Components\TextEntry::make('status_penggajian')
              ->label('Status')
              ->badge()
              ->color(fn(string $state): string => match ($state) {
                'Draf' => 'gray',
                'Diajukan' => 'primary',
                'Diverifikasi' => 'warning',
                'Disetujui' => 'success',
                'Ditolak' => 'danger',
                default => 'gray',
              }),

            Infolists\Components\TextEntry::make('catatan_penolakan_draf')
              ->label('Catatan Penolakan')
              ->placeholder('Tidak ada catatan penolakan')
              ->color('danger')
              ->visible(fn($record) => $record->status_penggajian === 'Ditolak'),

            Infolists\Components\TextEntry::make('total_gaji_from_db')
              ->label('Total Gaji')
              ->getStateUsing(function ($record): string {
                $totalGaji = $this->calculateTotalGajiFromDatabase($record);
                return 'Rp ' . number_format($totalGaji, 0, ',', '.');
              })
              ->weight('bold')
              ->color('success'),

            Infolists\Components\TextEntry::make('created_at')
              ->label('Dibuat Pada')
              ->dateTime('d F Y H:i'),
          ])
          ->columns(2),

        Infolists\Components\Section::make('Statistik Penggajian')
          ->schema([
            Infolists\Components\Grid::make(4)
              ->schema([
                Infolists\Components\TextEntry::make('total_gaji_pokok_from_db')
                  ->label('Total Gaji Pokok')
                  ->getStateUsing(function ($record): string {
                    $total = $this->calculateTotalGajiPokokFromDatabase($record);
                    return 'Rp ' . number_format($total, 0, ',', '.');
                  })
                  ->color('primary'),

                Infolists\Components\TextEntry::make('total_tunjangan_from_db')
                  ->label('Total Tunjangan')
                  ->getStateUsing(function ($record): string {
                    $total = $this->calculateTotalTunjanganFromDatabase($record);
                    return 'Rp ' . number_format($total, 0, ',', '.');
                  })
                  ->color('info'),

                Infolists\Components\TextEntry::make('total_lembur_from_db')
                  ->label('Total Upah Lembur')
                  ->getStateUsing(function ($record): string {
                    $total = $this->calculateTotalLemburFromDatabase($record);
                    return 'Rp ' . number_format($total, 0, ',', '.');
                  })
                  ->color('warning'),

                Infolists\Components\TextEntry::make('total_potongan_from_db')
                  ->label('Total Potongan')
                  ->getStateUsing(function ($record): string {
                    $total = $this->calculateTotalPotonganFromDatabase($record);
                    return 'Rp ' . number_format($total, 0, ',', '.');
                  })
                  ->color('danger'),
              ]),

            Infolists\Components\TextEntry::make('grand_total_from_db')
              ->label('GRAND TOTAL PENGGAJIAN')
              ->getStateUsing(function ($record): string {
                $total = $this->calculateTotalGajiFromDatabase($record);
                return 'Rp ' . number_format($total, 0, ',', '.');
              })
              ->size('xl')
              ->weight('bold')
              ->color('success')
              ->columnSpanFull(),
          ])
          ->collapsible(),

        Infolists\Components\Section::make('Detail Gaji Karyawan')
          ->schema([
            Infolists\Components\ViewEntry::make('karyawan_list')
              ->label('')
              ->view('filament.infolists.karyawan-gaji-detail')
              ->viewData([
                'karyawanData' => $karyawanData,
                'pagination' => $paginatedDetailPenggajian,
                'canEdit' => (
                  $this->record->status_penggajian === 'Draf' ||
                  $this->record->status_penggajian === 'Ditolak'
                ) && (Auth::user() && Auth::user()->role_id === 'R02'),
                'periodeBulan' => $this->record->periode_bulan,
                'periodeTahun' => $this->record->periode_tahun,
                'livewireId' => $this->getId(),
              ])
          ])
          ->collapsible()
          ->collapsed(false),
      ]);
  }

  /**
   * Get paginated detail penggajian from database
   */
  private function getPaginatedDetailPenggajianFromDatabase($record): LengthAwarePaginator
  {
    if (request()->has('page')) {
      $this->currentPage = max(1, request()->integer('page', $this->currentPage));
    }

    if ($this->paginationPath === '') {
      $this->paginationPath = request()->fullUrlWithoutQuery('page');
    }

    return Penggajian::where('periode_bulan', $record->periode_bulan)
      ->where('periode_tahun', $record->periode_tahun)
      ->with(['karyawan.golonganPtkp.kategoriTer'])
      ->paginate(10, ['*'], 'page', $this->currentPage)
      ->withPath($this->paginationPath)
      ->withQueryString();
  }

  /**
   * Process karyawan data using services - FIXED VERSION WITH LAZY LOADING
   */
  private function processKaryawanDataFromDatabase(LengthAwarePaginator $paginatedDetailPenggajian): array
  {
    $processedData = [];

    $periodeStart = Carbon::create($this->record->periode_tahun, $this->record->periode_bulan, 1)->startOfMonth();
    $periodeEnd = Carbon::create($this->record->periode_tahun, $this->record->periode_bulan, 1)->endOfMonth();

    $karyawanIds = collect($paginatedDetailPenggajian->items())->pluck('karyawan_id');

    $attendanceService = new AbsensiService();
    $attendanceData = $attendanceService->getCombinedDataBatch($karyawanIds, $periodeStart, $periodeEnd);

    foreach ($paginatedDetailPenggajian->items() as $detail) {
      $karyawan = $detail->karyawan;

      if (!$karyawan) {
        Log::warning("Karyawan not found for penggajian {$detail->penggajian_id}");
        continue;
      }

      $karyawanAttendance = $attendanceData[$karyawan->karyawan_id] ?? [
        'total_hadir' => 0,
        'total_alfa' => 0,
        'total_tidak_tepat' => 0,
        'total_cuti' => 0,
        'total_izin' => 0,
        'total_lembur_hours' => 0,
        'total_lembur_sessions' => 0,
      ];

      // USE SERVICES FOR ALL CALCULATIONS - WITH LAZY LOADING
      $tunjanganData = $this->getTunjanganService()->getTunjanganBreakdown($karyawan);
      $bpjsData = $this->getBpjsService()->calculateBpjsDeductions($karyawan);

      // USE LEMBUR SERVICE TO CALCULATE LEMBUR DATA
      $lemburData = $this->getLemburService()->calculateTotalLemburForPeriode($karyawan, $periodeStart, $periodeEnd);

      // Calculate Pph21 using actual values from database
      $pph21Data = $this->getPph21Service()->calculatePph21WithBreakdown(
        $karyawan,
        $detail->gaji_pokok,
        $detail->total_tunjangan,
        $detail->total_lembur
      );

      // Calculate potongan using services
      $potonganAlfaData = $this->getPotonganService()->calculateAlfaDeduction($karyawan, $karyawanAttendance['total_alfa']);
      $potonganTerlambatData = $this->getPotonganService()->calculateKeterlambatanDeduction($karyawan, $karyawanAttendance['total_tidak_tepat']);

      // BUILD BPJS BREAKDOWN WITH DESCRIPTIONS 
      $bpjsBreakdownWithDescriptions = [
        [
          'label' => 'BPJS Kesehatan',
          'amount' => $bpjsData['bpjs_kesehatan'],
          'description' => ((float) $bpjsData['breakdown']['persen_kesehatan'] * 100) . '% dari gaji pokok + tunjangan tetap (Rp ' . number_format($bpjsData['breakdown']['dasar_bpjs'], 0, ',', '.') . ')'
        ],
        [
          'label' => 'BPJS JHT',
          'amount' => $bpjsData['bpjs_jht'],
          'description' => ((float) $bpjsData['breakdown']['persen_jht'] * 100) . '% dari gaji pokok (Rp ' . number_format($detail->gaji_pokok, 0, ',', '.') . ')'
        ],
        [
          'label' => 'BPJS JP',
          'amount' => $bpjsData['bpjs_jp'],
          'description' => ((float) $bpjsData['breakdown']['persen_jp'] * 100) . '% dari gaji pokok + tunjangan tetap (Rp ' . number_format($bpjsData['breakdown']['dasar_bpjs'], 0, ',', '.') . ')'
        ],
      ];

      $processedData[] = [
        'detail_id' => $detail->penggajian_id,
        'karyawan_id' => $karyawan->karyawan_id,
        'status_penggajian' => $detail->status_penggajian,
        'sudah_ditransfer' => $detail->sudah_ditransfer,
        'nama_lengkap' => $karyawan->nama_lengkap,
        'jabatan' => $karyawan->jabatan,
        'departemen' => $karyawan->departemen ?? 'N/A',
        'total_hadir' => $karyawanAttendance['total_hadir'],
        'total_alfa' => $karyawanAttendance['total_alfa'],
        'total_tidak_tepat' => $karyawanAttendance['total_tidak_tepat'],
        'total_cuti' => $karyawanAttendance['total_cuti'],
        'total_izin' => $karyawanAttendance['total_izin'],
        'total_lembur' => $lemburData['total_jam'], // USE LEMBUR SERVICE DATA
        'total_lembur_sessions' => $lemburData['total_sesi'], // USE LEMBUR SERVICE DATA
        'gaji_pokok' => $detail->gaji_pokok,
        'tunjangan_total' => $detail->total_tunjangan,
        'tunjangan_breakdown' => $tunjanganData,
        'bpjs_breakdown' => [
          'breakdown' => $bpjsBreakdownWithDescriptions,
          'total_amount' => $bpjsData['total_bpjs'],
          'info' => $bpjsData['breakdown']
        ],
        'lembur_pay' => $detail->total_lembur,
        'lembur_detail' => [ // ADD LEMBUR BREAKDOWN
          'total_insentif' => $lemburData['total_insentif'],
          'total_jam' => $lemburData['total_jam'],
          'total_sesi' => $lemburData['total_sesi'],
          'formatted_amount' => $this->getLemburService()->formatRupiah($lemburData['total_insentif'])
        ],
        'potongan_total' => $detail->total_potongan,
        'total_gaji' => $detail->gaji_bersih,
        'penyesuaian' => $detail->penyesuaian,
        'catatan_penyesuaian' => $detail->catatan_penyesuaian,
        'pph21_detail' => [
          'jumlah' => $pph21Data['pph21_amount'],
          'tarif_persen' => $pph21Data['tarif_info']['tarif_persen'],
          'golongan_ptkp' => $pph21Data['ptkp_info']['golongan_ptkp'],
          'kategori_ter' => $pph21Data['ptkp_info']['kategori_ter'],
          'penghasilan_bruto' => $pph21Data['penghasilan_bruto'],
        ],
        'potongan_detail' => [
          'alfa' => [
            'total_potongan' => $detail->potongan_alfa,
            'potongan_per_hari' => $potonganAlfaData['potongan_per_hari'] ?? 0
          ],
          'keterlambatan' => [
            'total_potongan' => $detail->potongan_terlambat,
            'potongan_per_hari' => $potonganTerlambatData['potongan_per_hari'] ?? 0
          ],
          'bpjs' => $detail->potongan_bpjs,
          'pph21' => $detail->potongan_pph21,
        ],
      ];
    }

    return $processedData;
  }

  // Database calculation methods - USE DIRECT QUERIES
  private function getTotalKaryawanCountFromDatabase($record): int
  {
    return Penggajian::where('periode_bulan', $record->periode_bulan)
      ->where('periode_tahun', $record->periode_tahun)
      ->count();
  }

  private function calculateTotalGajiFromDatabase($record): float
  {
    return Penggajian::where('periode_bulan', $record->periode_bulan)
      ->where('periode_tahun', $record->periode_tahun)
      ->sum('gaji_bersih');
  }

  private function calculateTotalGajiPokokFromDatabase($record): float
  {
    return Penggajian::where('periode_bulan', $record->periode_bulan)
      ->where('periode_tahun', $record->periode_tahun)
      ->sum('gaji_pokok');
  }

  private function calculateTotalTunjanganFromDatabase($record): float
  {
    return Penggajian::where('periode_bulan', $record->periode_bulan)
      ->where('periode_tahun', $record->periode_tahun)
      ->sum('total_tunjangan');
  }

  private function calculateTotalLemburFromDatabase($record): float
  {
    return Penggajian::where('periode_bulan', $record->periode_bulan)
      ->where('periode_tahun', $record->periode_tahun)
      ->sum('total_lembur');
  }

  private function calculateTotalPotonganFromDatabase($record): float
  {
    return Penggajian::where('periode_bulan', $record->periode_bulan)
      ->where('periode_tahun', $record->periode_tahun)
      ->sum('total_potongan');
  }
}
