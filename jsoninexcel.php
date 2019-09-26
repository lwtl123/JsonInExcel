<?php

use JsonInExcel\Format\FormatPdf;

require 'vendor/autoload.php';



$input= new \JsonInExcel\Input\Input();
$data = $input->read($argv[1]);

$transform = new JsonInExcel\Transform\Transform();
$transformData = $transform->transformData($data);

$formatExcel = (new JsonInExcel\Format\Format())->setSheetFormat($transformData);
$formatpdf = (new FormatPdf())->setSheetFormat($transformData);

$outputExcel = new JsonInExcel\Output\OutputExcel();
$fileName = $transform->getMonth($data) . ' Arbeitszeiten';
$outputExcel->getExcelSheet($formatExcel, $fileName);

$outputpdf = new JsonInExcel\Output\OutputPdf();
$outputpdf->getPdfSheet($formatpdf, $fileName);



