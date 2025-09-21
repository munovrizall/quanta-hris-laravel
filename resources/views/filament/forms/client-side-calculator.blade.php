<script>
  function recalculateClientSide() {
    // Delay to ensure DOM is ready
    setTimeout(() => {
      // Get all the form values
      const gajiPokok = parseFormattedNumber(getFieldValue('gaji_pokok'));
      const totalTunjangan = parseFormattedNumber(getFieldValue('total_tunjangan'));
      const totalLembur = parseFormattedNumber(getFieldValue('total_lembur'));

      const potonganAlfa = parseFormattedNumber(getFieldValue('potongan_alfa'));
      const potonganTerlambat = parseFormattedNumber(getFieldValue('potongan_terlambat'));
      const potonganBpjs = parseFormattedNumber(getFieldValue('potongan_bpjs'));
      const potonganPph21 = parseFormattedNumber(getFieldValue('potongan_pph21'));
      const penyesuaian = parseFormattedNumber(getFieldValue('penyesuaian'));

      // Calculate totals
      const penghasilanBruto = gajiPokok + totalTunjangan + totalLembur;
      const totalPotongan = potonganAlfa + potonganTerlambat + potonganBpjs + potonganPph21;
      const gajiBersih = penghasilanBruto - totalPotongan + penyesuaian;

      // Update the display fields
      setFieldValue('penghasilan_bruto', formatNumber(penghasilanBruto));
      setFieldValue('total_potongan', formatNumber(totalPotongan));
      setFieldValue('gaji_bersih', formatNumber(Math.max(0, gajiBersih)));

    }, 100);
  }

  function getFieldValue(fieldName) {
    const field = document.querySelector(`input[data-field="${fieldName}"]`);
    return field ? field.value : '0';
  }

  function setFieldValue(fieldName, value) {
    const field = document.querySelector(`input[data-field="${fieldName}"]`);
    if (field) {
      field.value = value;
    }
  }

  function parseFormattedNumber(value) {
    if (!value || value === '') return 0;

    // Remove 'Rp ', dots, and convert comma to dot
    const cleaned = value.toString().replace(/Rp\s?/g, '').replace(/\./g, '').replace(/,/g, '.');
    const parsed = parseFloat(cleaned);
    return isNaN(parsed) ? 0 : parsed;
  }

  function formatNumber(num) {
    if (isNaN(num) || num === null || num === undefined) return '0';

    // Format number with Indonesian locale (dots as thousand separators)
    return Math.round(num).toLocaleString('id-ID');
  }

  // Initialize calculation when modal opens
  document.addEventListener('DOMContentLoaded', function () {
    setTimeout(() => {
      recalculateClientSide();
    }, 1000);
  });

  // Also initialize when Alpine.js components are ready
  if (window.Alpine) {
    Alpine.nextTick(() => {
      setTimeout(() => {
        recalculateClientSide();
      }, 500);
    });
  } else {
    // Fallback if Alpine is not yet available
    window.addEventListener('alpine:init', () => {
      Alpine.nextTick(() => {
        setTimeout(() => {
          recalculateClientSide();
        }, 500);
      });
    });
  }
</script>

<style>
  /* Optional: Add some styling for better UX */
  input[data-field] {
    transition: all 0.2s ease;
  }

  input[data-field]:focus {
    border-color: #0ea5e9;
    box-shadow: 0 0 0 1px #0ea5e9;
  }

  /* Highlight calculated fields */
  input[data-field="penghasilan_bruto"],
  input[data-field="total_potongan"],
  input[data-field="gaji_bersih"] {
    background-color: #f8fafc;
    font-weight: 500;
  }

  input[data-field="gaji_bersih"] {
    background-color: #ecfdf5;
    color: #059669;
    font-weight: 600;
  }
</style>