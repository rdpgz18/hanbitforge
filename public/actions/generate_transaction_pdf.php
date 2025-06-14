<?php
// Pastikan path ke tcpdf.php sudah benar sesuai lokasi Anda
require_once __DIR__ . '/../../app/config.php';
require_once __DIR__ . '/../../app/functions.php'; // Include file fungsi kebiasaan

require_once "../auth/auth.php";

require_once '../../app/tcpdf/tcpdf.php';

$user_id = $_SESSION['user_id'];

// Get date range from GET parameters
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// Basic validation for dates
if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $start_date) || !preg_match("/^\d{4}-\d{2}-\d{2}$/", $end_date)) {
    $start_date = date('Y-m-d', strtotime('-30 days'));
    $end_date = date('Y-m-d');
}

// Get transactions and totals for the selected date range
$transactions = getTransactionsByDateRange($pdo, $user_id, $start_date, $end_date);
$totals = getTotalsByDateRange($pdo, $user_id, $start_date, $end_date);

$totalIncome = $totals['income'];
$totalExpense = $totals['expense'];
$netBalance = $totalIncome - $totalExpense;

// Extend TCPDF to create custom Header and Footer
class MYPDF extends TCPDF
{
    private $user_id;

    public function setUserId($id)
    {
        $this->user_id = $id;
    }

    // Page header
    public function Header()
    {
        // Logo (optional)
        // $image_file = K_PATH_IMAGES.'logo_example.png';
        // $this->Image($image_file, 10, 10, 15, '', 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
        // Set font
        $this->SetFont('helvetica', 'B', 12);
        // Title
        $this->Cell(0, 15, 'Laporan Riwayat Transaksi Keuangan', 0, false, 'C', 0, '', 0, false, 'M', 'M');
        $this->Ln(); // New line
        $this->SetFont('helvetica', '', 10);
        $this->Cell(0, 10, 'Pengguna ID: ' . $this->user_id, 0, false, 'C', 0, '', 0, false, 'M', 'M');
        $this->Ln(15); // Spacer
    }

    // Page footer
    public function Footer()
    {
        // Position at 15 mm from bottom
        $this->SetY(-15);
        // Set font
        $this->SetFont('helvetica', 'I', 8);
        // Page number
        $this->Cell(0, 10, 'Halaman ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
        // Timestamp
        $this->Cell(0, 10, 'Dibuat pada: ' . date('d/m/Y H:i:s'), 0, false, 'R', 0, '', 0, false, 'T', 'M');
    }
}

// Create new PDF document
$pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->setUserId($user_id); // Set user ID for header

// Set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('HabitForge');
$pdf->SetTitle('Laporan Riwayat Transaksi');
$pdf->SetSubject('Riwayat Transaksi Keuangan Pengguna');
$pdf->SetKeywords('Transaksi, Keuangan, Laporan, PDF');

// Set default header data
$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE . ' 001', PDF_HEADER_STRING);

// Set header and footer fonts
$pdf->setHeaderFont(array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
$pdf->setFooterFont(array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

// Set default monospaced font
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

// Set margins
$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

// Set auto page breaks
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

// Set image scale factor
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

// Set some language-dependent strings (optional)
if (@file_exists(dirname(__FILE__) . '/lang/eng.php')) {
    require_once(dirname(__FILE__) . '/lang/eng.php');
    $pdf->setLanguageArray($l);
}

// Set font
$pdf->SetFont('helvetica', '', 10);

// Add a page
$pdf->AddPage();

// Content Title
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Write(0, 'Riwayat Transaksi dari ' . date('d F Y', strtotime($start_date)) . ' sampai ' . date('d F Y', strtotime($end_date)), '', 0, 'C', true, 0, false, false, 0);
$pdf->Ln(5);

// Summary Table
$pdf->SetFont('helvetica', '', 10);
$html_summary = '
<table border="0" cellpadding="5" cellspacing="0" style="width: 100%;">
    <tr>
        <td style="width: 33%; border: 1px solid #ddd; background-color: #e6ffe6;"><b>Total Pemasukan:</b></td>
        <td style="width: 33%; border: 1px solid #ddd; background-color: #ffe6e6;"><b>Total Pengeluaran:</b></td>
        <td style="width: 34%; border: 1px solid #ddd; background-color: #e6e6ff;"><b>Saldo Bersih:</b></td>
    </tr>
    <tr>
        <td style="width: 33%; border: 1px solid #ddd; color: #28a745;">' . formatRupiah($totalIncome) . '</td>
        <td style="width: 33%; border: 1px solid #ddd; color: #dc3545;">' . formatRupiah($totalExpense) . '</td>
        <td style="width: 34%; border: 1px solid #ddd; color: ' . ($netBalance >= 0 ? '#007bff' : '#dc3545') . ';">' . formatRupiah($netBalance) . '</td>
    </tr>
</table>
<br><br>';
$pdf->writeHTML($html_summary, true, false, true, false, '');

// Transactions Table
$html_transactions = '<table border="1" cellpadding="5" cellspacing="0" style="width: 100%; border-collapse: collapse;">
    <thead>
        <tr style="background-color: #f2f2f2;">
            <th style="width: 15%; text-align: left; font-weight: bold;">Tanggal</th>
            <th style="width: 30%; text-align: left; font-weight: bold;">Deskripsi</th>
            <th style="width: 20%; text-align: left; font-weight: bold;">Kategori</th>
            <th style="width: 15%; text-align: center; font-weight: bold;">Tipe</th>
            <th style="width: 20%; text-align: right; font-weight: bold;">Jumlah</th>
        </tr>
    </thead>
    <tbody>';

if (empty($transactions)) {
    $html_transactions .= '<tr><td colspan="5" style="text-align: center;">Tidak ada transaksi yang ditemukan untuk periode ini.</td></tr>';
} else {
    foreach ($transactions as $transaction) {
        $amount_color = ($transaction['type'] == 'expense') ? '#dc3545' : '#28a744'; // Red for expense, Green for income
        $sign = ($transaction['type'] == 'expense') ? '-' : '+';
        $transaction_type_display = ucfirst($transaction['type']);
        $transaction_category_display = htmlspecialchars($transaction['category'] ?? '-');

        $html_transactions .= '
        <tr>
            <td style="width: 15%;">' . date('d M Y', strtotime($transaction['transaction_date'])) . '</td>
            <td style="width: 30%;">' . htmlspecialchars($transaction['description']) . '</td>
            <td style="width: 20%;">' . $transaction_category_display . '</td>
            <td style="width: 15%; text-align: center;">' . $transaction_type_display . '</td>
            <td style="width: 20%; text-align: right; color: ' . $amount_color . ';">' . $sign . ' ' . formatRupiah($transaction['amount']) . '</td>
        </tr>';
    }
}
$html_transactions .= '</tbody></table>';

$pdf->writeHTML($html_transactions, true, false, true, false, '');

// Close and output PDF document
$pdf->Output('laporan_transaksi_' . $start_date . '_to_' . $end_date . '.pdf', 'D'); // 'D' will force download
exit();


?>