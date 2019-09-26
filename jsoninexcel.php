<?php

use JsonInExcel\OutputFiles;
use JsonInExcel\FormatPdf;

require 'vendor/autoload.php';



$input= new \JsonInExcel\Input();
$data = $input->read("/Users/ll/Desktop/jsonInexcel/jsonexport-08.json");

$transform = new JsonInExcel\Transform();
$transformData = $transform->transformData($data);

$formatExcel = (new JsonInExcel\Format())->setSheetFormat($transformData);
$formatpdf = (new FormatPdf())->setSheetFormat($transformData);

$outputExcel = new JsonInExcel\OutputExcel();
$fileName = $transform->getMonth($data) . ' Arbeitszeiten';
$outputExcel->getExcelSheet($formatExcel, $fileName);

$outputpdf = new JsonInExcel\OutputPdf();
$outputpdf->getPdfSheet($formatpdf, $fileName);



