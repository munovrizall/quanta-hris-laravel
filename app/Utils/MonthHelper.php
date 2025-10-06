<?php

namespace App\Utils;

class MonthHelper
{
  /**
   * Array nama bulan dalam Bahasa Indonesia
   */
  public const MONTH_NAMES = [
    1 => 'Januari',
    2 => 'Februari',
    3 => 'Maret',
    4 => 'April',
    5 => 'Mei',
    6 => 'Juni',
    7 => 'Juli',
    8 => 'Agustus',
    9 => 'September',
    10 => 'Oktober',
    11 => 'November',
    12 => 'Desember'
  ];

  /**
   * Get month name by number
   *
   * @param int $monthNumber
   * @return string
   */
  public static function getMonthName(int $monthNumber): string
  {
    return self::MONTH_NAMES[$monthNumber] ?? 'Unknown';
  }

  /**
   * Get formatted period string
   *
   * @param int $month
   * @param int $year
   * @return string
   */
  public static function formatPeriod(int $month, int $year): string
  {
    return self::getMonthName($month) . ' ' . $year;
  }

  /**
   * Get all month names for dropdown/select options
   *
   * @return array
   */
  public static function getMonthOptions(): array
  {
    return self::MONTH_NAMES;
  }

  /**
   * Get month number by name (case insensitive)
   *
   * @param string $monthName
   * @return int|null
   */
  public static function getMonthNumber(string $monthName): ?int
  {
    $monthName = ucfirst(strtolower(trim($monthName)));
    $monthNumbers = array_flip(self::MONTH_NAMES);
    return $monthNumbers[$monthName] ?? null;
  }

  /**
   * Get current month name
   *
   * @return string
   */
  public static function getCurrentMonthName(): string
  {
    return self::getMonthName((int) date('n'));
  }

  /**
   * Get previous month period
   *
   * @param int $month
   * @param int $year
   * @return array ['month' => int, 'year' => int, 'formatted' => string]
   */
  public static function getPreviousMonth(int $month, int $year): array
  {
    if ($month === 1) {
      $prevMonth = 12;
      $prevYear = $year - 1;
    } else {
      $prevMonth = $month - 1;
      $prevYear = $year;
    }

    return [
      'month' => $prevMonth,
      'year' => $prevYear,
      'formatted' => self::formatPeriod($prevMonth, $prevYear)
    ];
  }

  /**
   * Get next month period
   *
   * @param int $month
   * @param int $year
   * @return array ['month' => int, 'year' => int, 'formatted' => string]
   */
  public static function getNextMonth(int $month, int $year): array
  {
    if ($month === 12) {
      $nextMonth = 1;
      $nextYear = $year + 1;
    } else {
      $nextMonth = $month + 1;
      $nextYear = $year;
    }

    return [
      'month' => $nextMonth,
      'year' => $nextYear,
      'formatted' => self::formatPeriod($nextMonth, $nextYear)
    ];
  }
}