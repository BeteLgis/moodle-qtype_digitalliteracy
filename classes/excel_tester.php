<?php
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class qtype_digitalliteracy_excel_tester implements qtype_digitalliteracy_comparator
{

    public function compareFiles($source, $sample)
    {
        $spreadsheet_sample = IOFactory::load($sample);
        $spreadsheet_source = IOFactory::load($source);

        //var_dump($spreadsheet_sample->getSheetByName('projection')->getCellCollection()->getCoordinates());

//        $sheet_sample = $spreadsheet_sample->getSheetByName('projection');
//        $sheet_src = $spreadsheet_source->getSheetByName('projection');
        $cell_collection = $spreadsheet_source->getSheet(0)->getCellCollection()->getCoordinates();
        //var_dump($cell_collection);
        $matches = 0;
        $total = 0;

        foreach ($cell_collection as $cell => $value){
            $cell_1 = $spreadsheet_source->getSheet(0)->getCell($value
                , false);
            $cell_2 = $spreadsheet_sample->getSheet(0)->getCell($value
                , false);
            if (isset($cell_2) && $this->CompareCells($cell_1, $cell_2))
                $matches++;
            $total++;
        }
        //var_dump($matches / $total);
        //throw new Exception("a");
        return $matches / $total;
    }

    function CompareCells(Cell $cell_1,Cell $cell_2)
    {
        return $cell_1->getValue() == $cell_2->getValue();
    }

    public function validate()
    {
        // TODO: Implement validate() method.
    }
}