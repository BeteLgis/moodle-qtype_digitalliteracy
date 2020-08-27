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
class qtype_digitalliteracy_excel_tester extends qtype_digitalliteracy_tester_base {

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
            if (($count = $spreadsheet->getSheetCount()) !== 1)
                throw new Exception('Spreadsheet has '. $count. ' sheets ('.
                    implode(', ', $spreadsheet->getSheetNames()). '). Only 1 is supported (for now)!');
            $sheet = $spreadsheet->getSheet(0);
            $cell_collection = array_flip($sheet->getCellCollection()->getCoordinates());
            if (count($cell_collection) === 0)
                throw new Exception('Sheet with title '. $sheet->getTitle(). ' has 0 non-empty cells');
            foreach ($cell_collection as $coordinate => $index) {
                $cell = $sheet->getCell($coordinate, false);
                $cell->getCalculatedValue(); // looking for infinite loops (like 'F:F' range)
            }

            if (($count = $sheet->getChartCount()) !== 1)
                throw new Exception('Sheet  with title '. $sheet->getTitle(). ' has '.
                    $count. 'charts. Only 1 is supported (for now)!');
        } catch (Exception $ex) {
            $res->file = $filename;
            $res->msg = $ex->getMessage();
            return get_string('error_noreader', 'qtype_digitalliteracy', $res);
        }
        return '';
    }

    public function compare_files($data) {
        // preparing files and creating a reader
        $filetype = IOFactory::identify($data->response_path);
        $reader = IOFactory::createReader($filetype);
        $reader->setReadEmptyCells(false);
        $data->groupthreecoef > 0 ? $reader->setIncludeCharts(true) : $reader->setIncludeCharts(false);
//        $data->grouptwocoef == 0 && $data->groupthreecoef == 0 ? $reader->setReadDataOnly(true) :
//            $reader->setReadDataOnly(false); Deletes hidden rows!
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

        if ($data->validation)
            return array('fraction' => $fraction);
        $mistakes_name = 'Mistakes_' . $data->mistakes_name;
        $mistakes_path = $data->request_directory . '\\' . $mistakes_name;

        $writer = IOFactory::createWriter($spreadsheet_response, $filetype);
        $writer->setUseDiskCaching(true, $data->request_directory);
        $writer->setPreCalculateFormulas(false);
        $data->groupthreecoef > 0 ? $writer->setIncludeCharts(true) : $writer->setIncludeCharts(false);
        $writer->save($mistakes_path);
        return array('file_saver' => qtype_digitalliteracy_comparator::
        generate_question_file_saver(array($mistakes_name => $mistakes_path)), 'fraction' => $fraction);
    }

    /** Compare considering coefficients and
     * @return int fraction
     * @param $sheet_source Worksheet\Worksheet
     * @param $sheet_response Worksheet\Worksheet passed by reference as we mark mistakes there!
     * @param $sheet_template Worksheet\Worksheet
     */
    private function compare_with_coefficients($data, $sheet_source,
                                               &$sheet_response, $sheet_template) {
        $result = new stdClass(); // contains all comparison results as integer
        $result->value_matches = 0;
        $result->style_matches = 0;
        $result->cell_total = 0;
        $result->chart_matches = 0;
        $result->chart_total = 0;

        if ($data->grouponecoef > 0 || $data->grouptwocoef > 0) {
            $temp = $sheet_source->getCellCollection()->getCoordinates();
            $temp_2 = $sheet_response->getCellCollection()->getCoordinates();
            $cell_collection = array_merge(array_flip($temp), array_flip($temp_2));

            $types = $this->get_compare_types($data);

            foreach ($cell_collection as $coordinate => $index) {
                $cell_source = $sheet_source->getCell($coordinate, false);
                $cell_response = $sheet_response->getCell($coordinate, true);
                $cell_template = isset($sheet_template) ? $sheet_template
                    ->getCell($coordinate, false) : null;

                if (!$this->compare($types, $result, $cell_source, $cell_response, $cell_template) && !$data->validation)
                    $cell_response->getStyle()->getFill()->setFillType(
                        \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('ff0000');
            }
        }

        if ($data->groupthreecoef > 0) {
            $settings = $this->get_charts_compare($data);
            $this->compare_enclosures($settings, $sheet_source, $sheet_response, $result);
        }

        // preventing divide by zero
        if ($result->cell_total === 0)
            $result->cell_total = 1;
        if ($result->chart_total === 0)
            $result->chart_total = 1;

        // computing mark depending on correctly filled cell and (or) charts
        return ($data->grouponecoef * $result->value_matches +
                $data->grouptwocoef * $result->style_matches)
            / $result->cell_total / 100 + $data->groupthreecoef * $result->chart_matches / $result->chart_total / 100;
    }

    /**
     * @return array containing all cell comparison functions
     * (then called by {@link call_user_func()})
     * Such realization let us easily add new params and (or) coefficients
     * @link qtype_digitalliteracy_question::response_data()
     */
    private function get_compare_types($data) {
        $res = array();
        if ($data->grouponecoef) {
            $items = array();
            if ($data->grouponeparamone)
                $items[] = 'compare_value';
            if ($data->grouponeparamtwo)
                $items[] = 'compare_calculated_value';
            if ($data->grouponeparamthree)
                $items[] = 'compare_visibility';
            if ($data->grouponeparamfour)
                $items[] = 'compare_mergerange';
            $res[] = array('matches' => 'value_matches',
                'criterions' => $items);
        }
        if ($data->grouptwocoef) {
            $items = array();
            if ($data->grouptwoparamone)
                $items[] = 'compare_bold';
            if ($data->grouptwoparamtwo)
                $items[] = 'compare_fillcolor';
            if ($data->grouptwoparamthree)
                $items[] = 'compare_numberformat';
            if ($data->grouptwoparamfour)
                $items[] = 'compare_fonts';
            $res[] = array('matches' => 'style_matches',
                'criterions' => $items);
        }
        return $res;
    }

    private function get_charts_compare($data) {
        $items = array();
        if ($data->groupthreeparamone)
            $items[] = 'compare_chart_type';
        if ($data->groupthreeparamtwo)
            $items[] = 'compare_plot_values';
        if ($data->groupthreeparamthree)
            $items[] = 'compare_legend';
        if ($data->groupthreeparamfour)
            $items[] = 'compare_axis_x';
        return array('matches' => 'chart_matches',
            'criterions' => $items);
    }

    /** Compare cell to [corresponding] cell */
    private function compare(array $types, &$result, $cell_source, $cell_response, $cell_template) {
        $result->cell_total++;

        if (!isset($cell_source))
            return false;

        $res = $this->compare_cells($types, $cell_source, $cell_response);
        if (isset($cell_template)) {
            if ($res['equal'] && $this->compare_cells($types, $cell_source, $cell_template)['equal'])
                return true;
        }
//        if ($data->param && !$res['equal'])
//            return false;
        foreach ($types as $type) {
            if ($res[$type['matches']])
                $result->{$type['matches']}++;
        }
        return $res['equal'];
    }

    /** Cells are equal if all criterion values of all significant types
     * [significance > 0] match (are equal).
     * @param array $types {@link qtype_digitalliteracy_excel_tester::get_compare_types()}
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
     * @param array $criterions [value, calculated value (or bold, fill color) etc]
     * @return bool True if all criterions of the current comparison type [text, styles] are equal, false otherwise.
     */
    private function compare_cells_by_type(array $criterions, Cell $cell_1, Cell $cell_2) {
        $temp = 0;
        foreach ($criterions as $criterion) {
            if (call_user_func(array($this, $criterion), $cell_1, $cell_2))
                $temp++;
        }
        return $temp === count($criterions);
    }

    /** Visibility means filter visibility */
    function compare_visibility(Cell $cell_1, Cell $cell_2) {
        return $this->is_visible($cell_1) === $this->is_visible($cell_2);
    }

    function is_visible(Cell $cell) {
        $row_dimension = $cell->getWorksheet()->getRowDimension($cell->getRow());
        $column_dimension = $cell->getWorksheet()->getColumnDimension($cell->getColumn());
        return $row_dimension && $column_dimension ? $row_dimension->getVisible()
            && $column_dimension->getVisible() : null;
    }

    function compare_mergerange(Cell $cell_1, Cell $cell_2) {
        return $cell_1->getMergeRange() === $cell_2->getMergeRange();
    }

    function compare_value(Cell $cell_1, Cell $cell_2) {
        return $cell_1->getValue() === $cell_2->getValue();
    }

    function compare_calculated_value(Cell $cell_1, Cell $cell_2) {
        return $cell_1->getCalculatedValue() === $cell_2->getCalculatedValue();
    }

    function compare_bold(Cell $cell_1, Cell $cell_2) {
        return $cell_1->getStyle()->getFont()->getBold()
            === $cell_2->getStyle()->getFont()->getBold();
    }

    function compare_fillcolor(Cell $cell_1, Cell $cell_2) {
        return $cell_1->getStyle()->getFill()->getStartColor()->getARGB()
            === $cell_2->getStyle()->getFill()->getStartColor()->getARGB();
    }

    function compare_numberformat(Cell $cell_1, Cell $cell_2) {
        return $cell_1->getStyle()->getNumberFormat()->getFormatCode()
            === $cell_2->getStyle()->getNumberFormat()->getFormatCode();
    }

    function compare_fonts(Cell $cell_1, Cell $cell_2) {
        return $this->describe_font($cell_1)
            == $this->describe_font($cell_2);
    }

    function describe_font(Cell $cell) {
        $description = array();
        $font = $cell->getStyle()->getFont();
        $description[] = $font->getName();
        $description[] = $font->getSize();
        $description[] = $font->getUnderline();
        $description[] = $font->getColor()->getARGB();
        $description[] = $font->getItalic();
        return $description;
    }

    /**
     * Compares charts
     * @param $sheet_source Worksheet\Worksheet
     * @param $sheet_response Worksheet\Worksheet
     */
    function compare_enclosures($settings, $sheet_source, $sheet_response, &$result) {
        foreach ($sheet_source->getChartNames() as $chart_name) {
            $chart_source = $sheet_source->getChartByName($chart_name);
            $chart_response = $sheet_response->getChartByName($chart_name);
            $this->compare_charts($settings, $chart_source, $chart_response, $result);
        }
    }

    /**
     * @param $settings array as returned by {@link get_charts_compare()}
     */
    function compare_charts($settings, $source_chart, $response_chart, &$result) {
        foreach ($settings['criterions'] as $criterion) {
            if ($response_chart) {
                $res = call_user_func(array($this, $criterion), $source_chart, $response_chart);
                $result->{$settings['matches']}++;
            }
            $result->chart_total++;
        }
        $this->compare_plot_area($source_chart, $response_chart, $result);
    }

    function get_dataseries(Chart $chart) {
        $plot_area = $chart->getPlotArea();
        return $plot_area && $plot_area->getPlotGroupCount() === 1 ?
            $plot_area->getPlotGroupByIndex(0) : null;
    }

    function compare_chart_type(Chart $source_chart, Chart $response_chart) {
        $source_chart->getPlotArea()->getPlotGroup()[0]->getPlotType();
    }

    private function get_plot_type(Chart $chart) {
        $chart->getPlotArea()->getPlotGroup()[0]->getPlotType();
    }

    /** Compares plot area's data {@link \PhpOffice\PhpSpreadsheet\Chart\DataSeries} */
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

    function compare_legend() {

    }

    function compare_axis_x() {

    }
}

/** Could be used for filtered read */
class ChunkReadFilter implements IReadFilter {
    private $startRow = 0;
    private $endRow = 0;

    public function setRows($startRow, $chunkSize) {
        $this->startRow = $startRow;
        $this->endRow = $startRow + $chunkSize;
    }

    public function readCell($column, $row, $worksheetName = '') {
        //  Only read the heading row, and the rows that are configured in $this->_startRow and $this->_endRow
        if (($row == 1) || ($row >= $this->startRow && $row < $this->endRow)) {
            return true;
        }
        return false;
    }
}