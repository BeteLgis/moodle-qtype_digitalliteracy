<?php

defined('MOODLE_INTERNAL') || die();

use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use PhpOffice\PhpSpreadsheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Calculation\Calculation;
use PhpOffice\PhpSpreadsheet\Chart\Chart;

require_once($CFG->dirroot.'/question/type/digitalliteracy/question.php');

function shutDownFunction() {
    $error = error_get_last();
    if ($error['type'] === E_ERROR) {
        echo 'Unexpected error, please report to the developer on e-mail \'aasharipov@edu.hse.ru\'!';
    }
}
register_shutdown_function('shutDownFunction');

/** Excel file (spreadsheet) comparator */
class qtype_digitalliteracy_excel_tester extends qtype_digitalliteracy_compare_base
{
    public function validate_file($filepath, $filename) {
        $res = new stdClass();
        try {
            $this->start();
            $reader = IOFactory::createReaderForFile($filepath);
            $reader->setReadEmptyCells(false);
            $reader->setReadDataOnly(true);
            $reader->setIncludeCharts(false);
            $spreadsheet = $reader->load($filepath);
            Calculation::getInstance($spreadsheet)->disableCalculationCache();
            if (($count = $spreadsheet->getSheetCount()) != 1)
                throw new Exception('Spreadsheet has '. $count. ' sheets ('.
                    implode(', ', $spreadsheet->getSheetNames()). '). 1 required!');
            $sheet = $spreadsheet->getSheet(0);
            $cell_collection = array_flip($sheet->getCellCollection()->getCoordinates());
            if (count($cell_collection) === 0)
                throw new Exception('Sheet with title '. $sheet->getTitle(). ' has 0 non-empty cells');
            foreach ($cell_collection as $coordinate => $index) {
                $cell = $sheet->getCell($coordinate, false);
                $cell->getCalculatedValue(); // looking for infinite loops (like 'F:F' range)
            }
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
    public function compare_files($data)
    {
        // preparing files
        $filetype = IOFactory::identify($data->response_path);
        $reader = IOFactory::createReader($filetype);
        $reader->setReadEmptyCells(false);
        $data->thirdcoef > 0 ? $reader->setIncludeCharts(true) : $reader->setIncludeCharts(false);
        $data->secondcoef == 0 && $data->thirdcoef == 0 ? $reader->setReadDataOnly(true) :
            $reader->setReadDataOnly(false);
        $spreadsheet_response = $reader->load($data->response_path);
        $spreadsheet_source = $reader->load($data->source_path);
        Calculation::getInstance($spreadsheet_response)->disableCalculationCache();
        Calculation::getInstance($spreadsheet_source)->disableCalculationCache();
        $sheet_source = $spreadsheet_source->getSheet(0);
        $sheet_response = $spreadsheet_response->getSheet(0);

        $spreadsheet_template = null;
        if (isset($data->template_path)) {
            $spreadsheet_template = $reader->load($data->template_path);
            Calculation::getInstance($spreadsheet_template)->disableCalculationCache();
        }
        $sheet_template = isset($spreadsheet_template) ? $spreadsheet_template->getSheet(0) : null;
        $fraction = $this->compare_with_coefficients($data, $sheet_source,
            $sheet_response, $sheet_template);

        if ($data->flag)
            return array('fraction' => $fraction);
        $mistakes_name = 'Mistakes_' . $data->mistakes_name;
        $mistakes_path = $data->request_directory . '\\' . $mistakes_name;

        $writer = IOFactory::createWriter($spreadsheet_response, $filetype);
        $writer->setUseDiskCaching(true, $data->request_directory);
        $writer->setPreCalculateFormulas(false);
        $data->thirdcoef > 0 ? $writer->setIncludeCharts(true) : $writer->setIncludeCharts(false);
        $writer->save($mistakes_path);
        return array('file_saver' => qtype_digitalliteracy_comparator::
        generate_question_file_saver(array($mistakes_name => $mistakes_path)), 'fraction' => $fraction);
    }

    /**
     * @param $sheet_source Worksheet\Worksheet
     * @param $sheet_response Worksheet\Worksheet
     * @param $sheet_template Worksheet\Worksheet
     */
    private function compare_with_coefficients($data, $sheet_source,
                                               &$sheet_response, $sheet_template)
    {
//        echo 'Before load '. memory_get_usage()/1024.0 / 1024 . " MB \r\n"; TODO
        $temp = $sheet_source->getCellCollection()->getCoordinates();
        $temp_2 = $sheet_response->getCellCollection()->getCoordinates();
        $cell_collection = array_merge(array_flip($temp), array_flip($temp_2));

        $result = new stdClass();
        $result->value_matches = 0; $result->style_matches = 0; $result->cell_total = 0;
        $result->chart_matches = 0; $result->chart_total = 0;
        $types = $this->get_compare_types($data);

        foreach ($cell_collection as $coordinate => $index) {
            $cell_source = $sheet_source->getCell($coordinate, false);
            $cell_response = $sheet_response->getCell($coordinate, true);
            $cell_template = isset($sheet_template) ? $sheet_template
                ->getCell($coordinate, false) : null;

            if (!$this->compare($types, $result, $cell_source, $cell_response, $cell_template) && !$data->flag)
                $cell_response->getStyle()->getFill()->setFillType(
                    \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('ff0000');
        }

        if ($data->thirdcoef > 0)
            $this->compare_enclosures($sheet_source, $sheet_response, $result);

        if ($result->cell_total === 0)
            $result->cell_total = 1;
        if ($result->chart_total === 0)
            $result->chart_total = 1;

        return ($data->firstcoef * $result->value_matches +
                $data->secondcoef * $result->style_matches)
            / $result->cell_total / 100 + $data->thirdcoef * $result->chart_matches / $result->chart_total / 100;
    }


    private function get_compare_types($data) {
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
    private function compare_cells_by_type(array $criterions, Cell $cell_1, Cell $cell_2) {
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
        return $cell_1->getValue() === $cell_2->getValue();
    }

    function compare_calculated_value(Cell $cell_1, Cell $cell_2) {
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
     * @param $sheet_source Worksheet\Worksheet
     * @param $sheet_response Worksheet\Worksheet
     * @param $result
     */
    function compare_enclosures($sheet_source, $sheet_response, &$result) {
        foreach ($sheet_source->getChartNames() as $chart_name)
        {
            $chart_source = $sheet_source->getChartByName($chart_name);
            $chart_response = $sheet_response->getChartByName($chart_name);
            $this->compare_formatting($chart_source, $chart_response, $result);
        }
    }

    function compare_formatting($source, $response, &$result) {
//        if ($this->get_title($source->getTitle()) === $this->get_title($response->getTitle()))
//            $result->chart_matches++;
//        if ($this->get_title($source->getXAxisLabel()) === $this->get_title($response->getXAxisLabel()))
//            $result->chart_matches++;
//        if ($this->get_axis($source, true) === $this->get_axis($response, true))
//            $result->chart_matches++;
//        if ($this->get_title($source->getYAxisLabel()) === $this->get_title($response->getYAxisLabel()))
//            $result->chart_matches++;
//        if ($this->get_axis($source, false) === $this->get_axis($response, false))
//            $result->chart_matches++;
        $this->compare_plot_area($source, $response, $result);
        $result->chart_total += 0;
    }

    function get_title(\PhpOffice\PhpSpreadsheet\Chart\Title $title) {
        if ($title !== null) {
            $caption = implode(' ', $title->getCaption());
        } else {
            $caption = 'Untitled';
        }
        return $caption;
    }

    function get_axis(Chart $chart, bool $axisX) {
        $axis = $axisX ? $chart->getChartAxisX() : $chart->getChartAxisY();
        return $axis->getAxisNumberFormat() . $axis->getFillProperty('type') .
            $axis->getLineProperty('type');
    }

    function compare_plot_area($source_chart, $response_chart, &$result) {
        $source_plot_area = $source_chart->getPlotArea();
        if (!isset($source_plot_area))
            return;
        $response_plot_area = !$response_chart || is_null($response_chart->getPlotArea()) ?
            false : $response_chart->getPlotArea();
        for ($index = 0; $index < $source_plot_area->getPlotGroupCount(); $index++) {
            $source_plot_group = $this->getPlotGroup($source_plot_area, $index);
            if (!$source_plot_group) // should not happen
                continue;
            $response_plot_group = $this->getPlotGroup($response_plot_area, $index);
            for ($index_2 = 0; $index_2 < $source_plot_group->getPlotSeriesCount(); $index_2++) {
                $source_plot_values = $this->getPlotValues($source_plot_group, $index_2);
                if (!$source_plot_values) // should not happen
                    continue;
                $response_plot_values = $this->getPlotValues($response_plot_group, $index_2);

                $result->chart_total += count($source_plot_values);
                if (!$response_plot_values)
                    continue;
                $matches = count(array_intersect_assoc($source_plot_values, $response_plot_values));
                $matches -= abs(count($source_plot_values) - count($response_plot_values));
                if ($matches < 0)
                    $matches = 0;
                $result->chart_matches += $matches;
            }
        }
    }

    function getPlotGroup($area, $index) {
        if (!$area || !array_key_exists($index, $area->getPlotGroup()))
            return false;
        return $area->getPlotGroupByIndex($index);
    }

    function getPlotValues($dataSeries, $index) {
        if (!$dataSeries || !$dataSeries->getPlotValuesByIndex($index))
            return false;
        return $dataSeries->getPlotValuesByIndex($index)->getDataValues();
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