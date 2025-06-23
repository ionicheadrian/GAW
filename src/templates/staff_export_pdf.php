<?php

require_once '../config/config.php';
require_once dirname(__DIR__, 1) . '/lib/fpdf.php';

if (!is_logged_in() || !in_array(get_user_info()['role'], ['staff', 'admin'])) {
    header('Location: login.php');
    exit;
}

$period = $_GET['period'] ?? 'month';
$period_sql = '';
$period_label = 'Luna curenta';

switch ($period) {
    case 'day':
        $period_sql = 'DATE(deposit_date) = CURDATE()';
        $period_label = 'Astazi';
        break;
    case 'week':
        $period_sql = 'YEARWEEK(deposit_date, 1) = YEARWEEK(CURDATE(), 1)';
        $period_label = 'Saptamana curenta';
        break;
    case 'month':
    default:
        $period_sql = 'YEAR(deposit_date) = YEAR(CURDATE()) AND MONTH(deposit_date) = MONTH(CURDATE())';
        $period_label = 'Luna curenta';
        break;
}

$query = "SELECT wd.deposit_date, u.full_name, l.name as location, wc.type as waste_type, wd.quantity_kg, wd.notes
          FROM waste_deposits wd
          LEFT JOIN users u ON wd.user_id = u.id
          LEFT JOIN locations l ON wd.location_id = l.id
          LEFT JOIN waste_categories wc ON wd.waste_category_id = wc.id
          WHERE $period_sql
          ORDER BY wd.deposit_date DESC";
$result = mysqli_query($connection, $query);

$pdf = new FPDF('L', 'mm', 'A4');
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(0, 10, 'Depozitari - ' . $period_label, 0, 1, 'C');
$pdf->Ln(5);
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetFillColor(200,220,255);
$pdf->Cell(35, 8, 'Data', 1, 0, 'C', true);
$pdf->Cell(50, 8, 'Utilizator', 1, 0, 'C', true);
$pdf->Cell(50, 8, 'Locatie', 1, 0, 'C', true);
$pdf->Cell(40, 8, 'Tip deseu', 1, 0, 'C', true);
$pdf->Cell(30, 8, 'Cantitate (kg)', 1, 0, 'C', true);
$pdf->Cell(70, 8, 'Observatii', 1, 1, 'C', true);
$pdf->SetFont('Arial', '', 10);

if (mysqli_num_rows($result) === 0) {
    $pdf->Cell(275, 10, 'Nu exista depozitari pentru aceasta perioada.', 1, 1, 'C');
} else {
    while ($row = mysqli_fetch_assoc($result)) {
        $pdf->Cell(35, 8, date('d.m.Y H:i', strtotime($row['deposit_date'])), 1);
        $pdf->Cell(50, 8, $row['full_name'], 1);
        $pdf->Cell(50, 8, $row['location'], 1);
        $pdf->Cell(40, 8, ucfirst($row['waste_type']), 1);
        $pdf->Cell(30, 8, number_format($row['quantity_kg'], 1), 1);
        $pdf->Cell(70, 8, $row['notes'], 1);
        $pdf->Ln();
    }
}
$pdf->Output('D', 'depozitari_' . $period . '.pdf');
exit;
