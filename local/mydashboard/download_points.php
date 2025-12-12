<?php
require_once('../../config.php');
require_once 'lib.php';
global $DB, $USER;

require_login();

// Get points log
$points_log = get_my_points_log($USER->id);

$type = optional_param('type', 'csv', PARAM_ALPHA);

if ($type === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="points.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Emp ID','Fullname','Email','Point Type','Action','Points','Date Time']);
    foreach ($points_log as $log) {
        fputcsv($output, [
            $log->username,
            $log->firstname.' '.$log->lastname,
            $log->email,
            ucwords($log->point_type),
            $log->action,
            $log->points,
            date('d-m-Y H:i', $log->timecreated)
        ]);
    }
    fclose($output);
    exit;
}

if ($type === 'excel') {
    require_once $CFG->libdir.'/phpexcel/PHPExcel.php';
    $objPHPExcel = new PHPExcel();
    $objPHPExcel->setActiveSheetIndex(0);
    $sheet = $objPHPExcel->getActiveSheet();
    $sheet->fromArray(['Emp ID','Fullname','Email','Point Type','Action','Points','Date Time'], NULL, 'A1');

    $rowNum = 2;
    foreach ($points_log as $log) {
        $sheet->setCellValue('A'.$rowNum, $log->username);
        $sheet->setCellValue('B'.$rowNum, $log->firstname.' '.$log->lastname);
        $sheet->setCellValue('C'.$rowNum, $log->email);
        $sheet->setCellValue('D'.$rowNum, ucwords($log->point_type));
        $sheet->setCellValue('E'.$rowNum, $log->action);
        $sheet->setCellValue('F'.$rowNum, $log->points);
        $sheet->setCellValue('G'.$rowNum, date('d-m-Y H:i', $log->timecreated));
        $rowNum++;
    }

    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="points.xls"');
    header('Cache-Control: max-age=0');

    $writer = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
    $writer->save('php://output');
    exit;
}

if ($type === 'pdf') {
    require_once $CFG->libdir.'/tcpdf/tcpdf.php';
    $pdf = new TCPDF();
    $pdf->AddPage();
    $html = '<h2>My Points Log</h2><table border="1" cellpadding="4">
    <tr>
        <th>Emp ID</th><th>Fullname</th><th>Email</th><th>Point Type</th><th>Action</th><th>Points</th><th>Date Time</th>
    </tr>';

    foreach ($points_log as $log) {
        $html .= '<tr>
            <td>'.$log->username.'</td>
            <td>'.$log->firstname.' '.$log->lastname.'</td>
            <td>'.$log->email.'</td>
            <td>'.ucwords($log->point_type).'</td>
            <td>'.$log->action.'</td>
            <td>'.$log->points.'</td>
            <td>'.date('d-m-Y H:i', $log->timecreated).'</td>
        </tr>';
    }

    $html .= '</table>';
    $pdf->writeHTML($html);
    $pdf->Output('points.pdf', 'D');
    exit;
}
?>
