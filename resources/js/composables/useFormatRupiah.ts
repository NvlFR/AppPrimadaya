/**
 * Composable terpusat untuk pemformatan mata uang Rupiah (IDR).
 * Digunakan di seluruh halaman agar format konsisten dan tidak duplikat.
 */

const formatter = new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
});

export function useFormatRupiah() {
    /**
     * Format angka menjadi string Rupiah.
     * Contoh: 50000 → "Rp 50.000"
     */
    const formatRupiah = (value: number | string | null | undefined): string => {
        if (value === null || value === undefined || value === '') return 'Rp 0';
        return formatter.format(Number(value));
    };

    /**
     * Format angka menjadi string Rupiah singkat (tanpa simbol "Rp").
     * Contoh: 50000 → "50.000"
     */
    const formatAngka = (value: number | string | null | undefined): string => {
        if (value === null || value === undefined || value === '') return '0';
        return Number(value).toLocaleString('id-ID');
    };

    return { formatRupiah, formatAngka };
}
