<?php

defined('MOODLE_INTERNAL') || die();

use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use PhpOffice\PhpSpreadsheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

require_once($CFG->dirroot.'/question/type/digitalliteracy/question.php');

/** Excel file (spreadsheet) comparator */
class qtype_digitalliteracy_excel_tester implements qtype_digitalliteracy_compare_interface
{
    /** Main comparison method
     * @param array $data
     * @return array {@link question_file_saver} and int fraction
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    public function compare_files(array $data)
    {
        $response_path = $data['response_path'];
        $source_path = $data['source_path'];
        $coef = new stdClass();
        $coef->value = $data['coef_value'];
        $coef->format = $data['coef_format'];
        $coef->chart = $data['coef_enclosures'];

        $filetype = IOFactory::identify($response_path);
        $reader = IOFactory::createReader($filetype);
        $coef->chart > 0 ? $reader->setIncludeCharts(true) : $reader->setIncludeCharts(false);
//        $coef->format == 0 && $coef->chart == 0 ? $reader->setReadDataOnly(true) :
//            $reader->setReadDataOnly(false);

        $spreadsheet_response = $reader->load($response_path);
        $spreadsheet_source = $reader->load($source_path);

        $fraction = $this->compare_with_coefficients($spreadsheet_response, $spreadsheet_source, $coef);

        $mistakes_name = 'Mistakes_' . $data['mistakes_name'];
        $mistakes_path = $data['request_directory'] . '\\' . $mistakes_name;
        $writer = IOFactory::createWriter($spreadsheet_response, $filetype);
        $writer->save($mistakes_path);
        return array('file_saver' => qtype_digitalliteracy_comparator::generate_question_file_saver($mistakes_name, $mistakes_path),
           'fraction' => $fraction);
    }

    /**
     * @param $response Spreadsheet
     * @param $source Spreadsheet
     */
    private function compare_with_coefficients(&$response, &$source, $coef) {
        $temp = $source->getSheet(0)->getCellCollection()->getCoordinates();
        $temp_2 = $response->getSheet(0)->getCellCollection()->getCoordinates();
        $cell_collection = array_merge(array_flip($temp), array_flip($temp_2));
        $coef->value_matches = 0; $coef->format_matches = 0;
        $coef->chart_matches = 0; $coef->total = 0;
        $coef->chart_total = 0;
        foreach ($cell_collection as $value => $index) {
            $cell_1 = $source->getSheet(0)->getCell($value
                , false);
            $cell_2 = $response->getSheet(0)->getCell($value
                , true);
            if (!$this->compare($coef, $cell_1, $cell_2))
                $cell_2->getStyle()->getFill()->setFillType(
                    \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('ff0000');
        }

        $this->compare_cells_by_enclosures($source, $response, $coef);
        return !isset($coef->total) || $coef->total == 0 ? 0 :
            ($coef->value * $coef->value_matches + $coef->format * $coef->format_matches +
            $coef->chart * $coef->chart_matches) / $coef->total / 100;
    }

    private function compare(&$coef, $cell_1, $cell_2) {
        if (!isset($cell_1))
            return false;
        $temp = 0;
        if ($this->compare_cells_by_value($cell_1, $cell_2, $coef->value)) {
            $coef->value_matches++;
            $temp++;
        }
        if ($this->compare_cells_by_formats($cell_1, $cell_2, $coef->format)) {
            $coef->format_matches++;
            $temp++;
        }
        $coef->total++;
        return $temp === 2;
    }

    private function test() {
        global $CFG;
        $path = $CFG->dirroot.'/question/type/digitalliteracy/classes/';
        $sheet = IOFactory::load($path. 'test.xlsx');
        var_dump($sheet->getSheet(0)->getCell('A1')->getStyle()->getFill());
//        $writer = IOFactory::createWriter($sheet, 'Xlsx');
//        $writer->save($path. 'anime.xlsx');
        throw new Exception('a');
    }

    /** Cell value comparison */
    function compare_cells_by_value(Cell $cell_1,Cell $cell_2, $coefficient) {
        if ($coefficient <= 0)
            return true;
        return $cell_1->getDataType() == $cell_2->getDataType() &&
            $cell_1->getValue() == $cell_2->getValue();
    }

    function compare_cells_by_formats(Cell $cell_1,Cell $cell_2, $coefficient) {
        if ($coefficient <= 0)
            return true;
        return $cell_1->getFormattedValue() === $cell_2->getFormattedValue();
    }

    /**
     * @param $source Spreadsheet
     * @param $response Spreadsheet
     * @param $coef
     * @return bool
     */
    function compare_cells_by_enclosures($source, $response, &$coef) {
        $sheet_source = $source->getSheet(0);
        $sheet_response = $response->getSheet(0);

        foreach ($sheet_source->getChartNames() as $i => $name)
        {
            $chart_source = $sheet_source->getChartByName($name);
            var_dump($chart_source->getPlotArea());
            throw new Exception('a');
            if ($chart = $sheet_response->getChartByName($name)) {
                // Comparison
                if ($chart->getTitle() !== null) {
                    $caption = '"' . implode(' ', $chart->getTitle()->getCaption()) . '"';
                } else {
                    $caption = 'Untitled';
                }
                $groupCount = $chart->getPlotArea()->getPlotGroupCount();
                if ($groupCount == 1) {
                    $chartType = $chart->getPlotArea()->getPlotGroupByIndex(0)->getPlotType();
                } else {
                    $chartTypes = [];
                    for ($i = 0; $i < $groupCount; ++$i) {
                        $chartTypes[] = $chart->getPlotArea()->getPlotGroupByIndex($i)->getPlotType();
                    }
                }
            }
        }
    }
    /** Files validation */
    public function validate_filearea($question, $filearea, $dir)
    {
        $damaged = array();
        $files = qtype_digitalliteracy_question::get_filearea_files($question, $filearea, $dir);
        var_dump($files);
        foreach ($files as $file) {
            if ($this->validate_file($file))
                $damaged[] = $file;
        }
        return $damaged;
    }

    public function validate_file($file)
    {
        // $ext = strtolower(substr($file, strrpos($file, '.') + 1));
        try {
             IOFactory::createReaderForFile($file);
        } catch (Exception $ex) {
            return true;
        }
//        $spreadsheet = IOFactory::load($file);
//        if ($spreadsheet->getSheetCount() == 0)
//            return true;
        return false;
    }
}
/**  Define a Read Filter class implementing IReadFilter  */
class ChunkReadFilter implements IReadFilter
{
    private $startRow = 0;

    private $endRow = 0;

    /**
     * Set the list of rows that we want to read.
     *
     * @param mixed $startRow
     * @param mixed $chunkSize
     */
    public function setRows($startRow, $chunkSize)
    {
        $this->startRow = $startRow;
        $this->endRow = $startRow + $chunkSize;
    }

    public function readCell($column, $row, $worksheetName = '')
    {
        //  Only read the heading row, and the rows that are configured in $this->_startRow and $this->_endRow
        if (($row == 1) || ($row >= $this->startRow && $row < $this->endRow)) {
            return true;
        }

        return false;
    }
}
