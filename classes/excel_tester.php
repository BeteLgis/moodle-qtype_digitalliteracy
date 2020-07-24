<?php

defined('MOODLE_INTERNAL') || die();

use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use PhpOffice\PhpSpreadsheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Calculation\Calculation;

require_once($CFG->dirroot.'/question/type/digitalliteracy/question.php');

function shutDownFunction() {
    $error = error_get_last();
    // Fatal error, E_ERROR === 1
    if ($error['type'] === E_ERROR) {
        // Do your stuff
        echo 'Unexpected error [usually memory management]';
    }
}
register_shutdown_function('shutDownFunction');

/** Excel file (spreadsheet) comparator */
class qtype_digitalliteracy_excel_tester implements qtype_digitalliteracy_compare_interface
{
    public function validate_file($filepath, $filename) {
        $res = new stdClass();
        try {
            $reader = IOFactory::createReaderForFile($filepath);
            $spreadsheet = $reader->load($filepath);
            if ($spreadsheet->getSheetCount() == 0)
                throw new Exception("Spreadsheet has 0 sheets.");
            $spreadsheet->getSheet(0)->getCellCollection();
        } catch (Exception $ex) {
            $res->file = $filename;
            $res->msg = $ex->getMessage();
            return get_string('error_noreader', 'qtype_digitalliteracy', $res);
        }
        return '';
    }
    /** Main comparison method
     * @return array {@link question_file_saver} and int fraction
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    public function compare_files(&$data)
    {
        // preparing files
        $filetype = IOFactory::identify($data->response_path);
        $reader = IOFactory::createReader($filetype);
        $reader->setReadEmptyCells(false);
        $data->thirdcoef > 0 ? $reader->setIncludeCharts(true) : $reader->setIncludeCharts(false);
//        $data->coef_format == 0 && $data->coef_enclosures == 0 ? $reader->setReadDataOnly(true) :
//            $reader->setReadDataOnly(false);

        $spreadsheet_response = $reader->load($data->response_path);
        $spreadsheet_source = $reader->load($data->source_path);
        Calculation::getInstance($spreadsheet_response)->disableCalculationCache();
        Calculation::getInstance($spreadsheet_source)->disableCalculationCache();

        if (isset($data->template_path)) {
            $spreadsheet_template = $reader->load($data->template_path);
            Calculation::getInstance($spreadsheet_template)->disableCalculationCache();
        }

        $fraction = $this->compare_with_coefficients($spreadsheet_response,
            $spreadsheet_source, $spreadsheet_template, $data);
        if ($data->flag)
            return array('fraction' => $fraction);
        $mistakes_name = 'Mistakes_' . $data->mistakes_name;
        $mistakes_path = $data->request_directory . '\\' . $mistakes_name;
        $writer = IOFactory::createWriter($spreadsheet_response, $filetype);
        $writer->save($mistakes_path);
        $writer->setUseDiskCaching(true, $data->request_directory);
        return array('file_saver' => qtype_digitalliteracy_comparator::
        generate_question_file_saver($mistakes_name, $mistakes_path), 'fraction' => $fraction);
    }

    /**
     * @param $response Spreadsheet
     * @param $source Spreadsheet
     * @param $template Spreadsheet
     */
    private function compare_with_coefficients(&$response, &$source, &$template, &$data) {
        $temp = $source->getSheet(0)->getCellCollection()->getCoordinates();
        $temp_2 = $response->getSheet(0)->getCellCollection()->getCoordinates();
        $cell_collection = array_merge(array_flip($temp), array_flip($temp_2));

        $result = new stdClass();
        $result->value_matches = 0; $result->style_matches = 0; $result->cell_total = 0;
        $result->chart_matches = 0; $result->chart_total = 0;
        $types = $this->get_compare_types($data);

        foreach ($cell_collection as $coordinate => $index) {
            $cell_source = $source->getSheet(0)->getCell($coordinate, false);
            $cell_response = $response->getSheet(0)->getCell($coordinate, true);
            $cell_template = isset($template) ? $template->getSheet(0)->getCell($coordinate, false) : null;

            if (!$this->compare($types, $result, $cell_source, $cell_response, $cell_template) && !$data->flag)
                $cell_response->getStyle()->getFill()->setFillType(
                    \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('ff0000');
        }

//        $this->compare_cells_by_enclosures($source, $response, $data);
        return $result->cell_total == 0 ? 0 : ($data->firstcoef * $result->value_matches +
                $data->secondcoef * $result->style_matches) // + $data->chart * $data->chart_matches)
            / $result->cell_total / 100;
    }


    private function get_compare_types(&$data) {
        $res = array();
        if ($data->firstcoef) {
            $items = array();
            if ($data->paramvalue)
                $items[] = 'compare_value';
            if ($data->paramtype)
                $items[] = 'compare_datatype';
            $res[] = array('matches' => 'value_matches',
                'criterions' => $items);
        }
        if ($data->secondcoef) {
            $items = array();
            if ($data->parambold)
                $items[] = 'compare_bold';
            if ($data->paramfillcolor)
                $items[] = 'compare_fillcolor';
            $res[] = array('matches' => 'style_matches',
                'criterions' => $items);
        }
        return $res;
    }

    private function compare(array $types, &$result, $cell_source, $cell_response, $cell_template) {
        if (!isset($cell_source)) {
            $result->cell_total++;
            return false;
        }

        $res = $this->compare_cells($types, $cell_source, $cell_response);
        if (isset($cell_template)) {
            if ($res['equal'] && $this->compare_cells($types, $cell_source, $cell_template)['equal'])
                return true;
        }
        foreach ($types as $type) {
            if ($res[$type['matches']])
                $result->{$type['matches']}++;
        }
        $result->cell_total++;
        return $res['equal'];
    }

    /**
     * @param array $types represents different types of a cell comparison (text or styles)
     * Cells are equal if all criterions of all significant types [significance > 0] are equal.
     */
    private function compare_cells(array $types, Cell $cell_1, Cell $cell_2) {
        $temp = 0;
        $result = array();
        foreach ($types as $type) {
            $result[$type['matches']] = false;
            if ($this->compare_cells_by_type($type['criterions'], $cell_1, $cell_2)) {
                $temp++;
                $result[$type['matches']] = true;
            }
        }
        $result['equal'] = $temp === count($types);
        return $result;
    }

    /**
     * @param array $criterions [value, data type or bold, fill color]
     * @return bool True if all criterions of the current comparison type [text, styles] are equal, false otherwise.
     */
    private function compare_cells_by_type(array &$criterions, Cell $cell_1, Cell $cell_2) {
        $temp = 0;
        if (!$this->compare_visibility($cell_1, $cell_2))
            return false;
        foreach ($criterions as $criterion) {
            if (call_user_func(array($this, $criterion), $cell_1, $cell_2))
                $temp++;
        }
        return $temp === count($criterions);
    }

    function compare_visibility(Cell $cell_1, Cell $cell_2) {
        return $this->is_visible($cell_1) === $this->is_visible($cell_2);
    }

    function is_visible(Cell $cell) {
        return $cell->getWorksheet()->getRowDimension($cell->getRow())->getVisible()
            && $cell->getWorksheet()->getColumnDimension($cell->getColumn())->getVisible();
    }

    function compare_value(Cell $cell_1, Cell $cell_2) {
        return $cell_1->getCalculatedValue() === $cell_2->getCalculatedValue();
    }

    function compare_datatype(Cell $cell_1, Cell $cell_2) {
        return $cell_1->getDataType() === $cell_2->getDataType();
    }

    function compare_bold(Cell $cell_1, Cell $cell_2) {
        return $cell_1->getStyle()->getFont()->getBold()
            === $cell_2->getStyle()->getFont()->getBold();
    }

    function compare_fillcolor(Cell $cell_1, Cell $cell_2) {
        return $cell_1->getStyle()->getFill()->getStartColor()->getARGB()
            === $cell_2->getStyle()->getFill()->getStartColor()->getARGB();
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