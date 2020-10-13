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

/** Excel file (spreadsheet) comparator */
class qtype_digitalliteracy_excel_tester extends qtype_digitalliteracy_tester_base {

    public static function get_strings() {
        return array('error_coordinate_394' => get_string('error_coordinate_394', 'qtype_digitalliteracy'),
            'error_stringhelper_481' => get_string('error_stringhelper_481', 'qtype_digitalliteracy'),
            'error_xlsx_442' => get_string('error_stringhelper_481', 'qtype_digitalliteracy'),
            'error_sheetlimit' => get_string('error_sheetlimit', 'qtype_digitalliteracy'),
            'error_zerocells' => get_string('error_zerocells', 'qtype_digitalliteracy'));
    }

    public function validate_file() {
        $func = function () {
            $reader = IOFactory::createReaderForFile($this->data->fullpath);
            $reader->setReadEmptyCells(false);
            $reader->setIncludeCharts(true);
            $spreadsheet = $reader->load($this->data->fullpath);
            Calculation::getInstance($spreadsheet)->disableCalculationCache();
            if (($count = $spreadsheet->getSheetCount()) !== 1) {
                return $this->data->fork ? get_string('error_sheetlimit', 'qtype_digitalliteracy') :
                    $this->data->errors['error_sheetlimit'];
            }
            $sheet = $spreadsheet->getSheet(0);
            $cell_collection = array_flip($sheet->getCellCollection()->getCoordinates());
            if (count($cell_collection) === 0) {
                $res = new stdClass();
                $res->title = $sheet->getTitle();
                return $this->data->fork ? get_string('error_zerocells', 'qtype_digitalliteracy') :
                    sprintf($this->data->errors['error_zerocells'], $res->title);
            }
            foreach ($cell_collection as $coordinate => $index) {
                $cell = $sheet->getCell($coordinate, false);
                $cell->getCalculatedValue(); // looking for infinite loops (like 'F:F' range)
            }
            return '';
        };
        $error = $func();
        if (!empty($error)) {
            $res = new stdClass();
            $res->file = $this->data->filename;
            $res->msg = $error;
            $message = $this->data->fork ? get_string('error_noreader', 'qtype_digitalliteracy', $res)
                : sprintf($this->data->errors['error_noreader'], $res->file, $res->msg);
            throw new Exception($message);
        }
        return array();
    }

    public function compare_files() {
        // preparing files and creating a reader
        $filetype = IOFactory::identify($this->data->response_path);
        $reader = IOFactory::createReader($filetype);
        $reader->setReadEmptyCells(false);
        $this->data->groupthreecoef > 0 ? $reader->setIncludeCharts(true) :
            $reader->setIncludeCharts(false);

        $spreadsheet_response = $reader->load($this->data->response_path);
        $spreadsheet_source = $reader->load($this->data->source_path);
        $spreadsheet_template = isset($this->data->template_path) ?
            $reader->load($this->data->template_path) : null;

        Calculation::getInstance($spreadsheet_response)->disableCalculationCache(); // not needed as we compare in one run
        Calculation::getInstance($spreadsheet_source)->disableCalculationCache();
        if ($spreadsheet_template) {
            Calculation::getInstance($spreadsheet_template)->disableCalculationCache();
        }

        $sheet_source = $spreadsheet_source->getSheet(0);
        $sheet_response = $spreadsheet_response->getSheet(0);
        $sheet_template = $spreadsheet_template ? $spreadsheet_template->getSheet(0) : null;

        $writer = IOFactory::createWriter($spreadsheet_response, $filetype);
        list($fraction, $files) = $this->compare_with_coefficients($sheet_source,
            $sheet_response, $sheet_template, $writer);

        if ($this->data->validation)
            return array('fraction' => $fraction);

        return array('files' => $files, 'fraction' => $fraction);
    }

    /** Compare considering coefficients and
     * @return array files and fraction
     * @param $sheet_source Worksheet\Worksheet
     * @param $sheet_response Worksheet\Worksheet passed by reference as we mark mistakes there!
     * @param $sheet_template Worksheet\Worksheet
     * @param $writer PhpOffice\PhpSpreadsheet\Writer\IWriter
     */
    private function compare_with_coefficients($sheet_source, &$sheet_response, $sheet_template, $writer) {
        $result = array(); // contains a mark for each comparison group
        $files = array(); // file name => path for each mistake file

        $cell_describer = new CellDescriber();
        $cell_describer->compare_sheets($this->data, $result, $sheet_source, $sheet_response, $sheet_template);

        $chart_describer = new ChartDescriber();
        if (!empty($mistakes = $chart_describer->compare_sheets($this->data, $result,
                $sheet_source, $sheet_response, $writer)) && !$this->data->validation) {
            $name = 'Mistakes_charts.txt';
            $path = $this->data->request_directory . '/' . $name;

            foreach ($mistakes as $chartname => $errors) {
                $str = str_repeat('-', 10);
                file_put_contents($path, $str. 'Chart "'. $chartname.'"' . $str. PHP_EOL, FILE_APPEND);
                file_put_contents($path, implode(PHP_EOL, $errors), FILE_APPEND);
            }
            $files[$name] = $path;
        }

        $res = array_sum($result);
        if ($res != 1 && !$this->data->validation) {
            $mistakes_name = 'Mistakes_' . $this->data->mistakes_name;
            $mistakes_path = $this->data->request_directory . '/' . $mistakes_name;
            $writer->setUseDiskCaching(true, $this->data->request_directory);
            $writer->setPreCalculateFormulas(false);
            $writer->save($mistakes_path);
            $files[$mistakes_name] = $mistakes_path;
        }

        // computing final mark
        return array($res, $files);
    }
}

class CellDescriber extends Describer {
    /**
     * @return array containing all cell comparison functions
     * (then called by {@link call_user_func()})
     * Such realization let us easily add new params and (or) coefficients
     * @link qtype_digitalliteracy_question::response_data()
     */
    function get_settings($data) {
        $res = array();
        if ($data->grouponecoef) {
            $items = array();
            if ($data->grouponeparamone)
                $items['value'] = 'get_value';
            if ($data->grouponeparamtwo)
                $items['calculated_value'] = 'get_calculated_value';
            if ($data->grouponeparamthree)
                $items['visibility'] = 'get_visibility';
            if ($data->grouponeparamfour)
                $items['mergerange'] = 'get_mergerange';
            $res[] = array('group' => 'value','coef' => $data->grouponecoef,
                'criterions' => $items);
        }
        if ($data->grouptwocoef) {
            $items = array();
            if ($data->grouptwoparamone)
                $items['bold'] = 'get_bold';
            if ($data->grouptwoparamtwo)
                $items['fillcolor'] = 'get_fillcolor';
            if ($data->grouptwoparamthree)
                $items['numberformat'] = 'get_numberformat';
            if ($data->grouptwoparamfour)
                $items['font'] = 'get_font_description';
            $res[] = array('group' => 'style','coef' => $data->grouptwocoef,
                'criterions' => $items);
        }
        return $res;
    }

    /**
     * @param $sheet_source Worksheet\Worksheet
     * @param $sheet_response Worksheet\Worksheet
     * @param $sheet_template Worksheet\Worksheet
     */
    public function compare_sheets($data, &$result, $sheet_source, &$sheet_response, $sheet_template = null) {
        $settings = $this->get_settings($data);
        if (!empty($settings)) {
            $temp = $sheet_source->getCellCollection()->getCoordinates();
            $temp_2 = $sheet_response->getCellCollection()->getCoordinates();
            $cell_collection = array_merge(array_flip($temp), array_flip($temp_2));

            $counter = array();
            foreach ($settings as $setting) {
                $counter[$setting['group']] = 0;
            }
            $counter['total'] = 0;

            foreach ($cell_collection as $coordinate => $index) {
                $source_cell = $sheet_source->getCell($coordinate, false);
                $response_cell = $sheet_response->getCell($coordinate, true);
                $template_cell = $sheet_template ? $sheet_template->getCell($coordinate, false) : null;

                if ($this->compare_cells($settings, $source_cell,
                        $response_cell, $template_cell, $counter) && !$data->validation)
                    $response_cell->getStyle()->getFill()->setFillType(
                        \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('ff0000');
            }

            $total = $counter['total'] !== 0 ? $counter['total'] : 1;
            foreach ($settings as $setting) {
                $group = $setting['group'];
                $result[$group] = $setting['coef'] * $counter[$group] / $total / 100;
            }
        }
    }

    function compare_cells($settings, $source_cell, $response_cell, $template_cell, &$counter) {
        $exclude = 0;
        $correct = 0;
        $temp = array();
        foreach ($settings as $setting) {
            $group = $setting['group'];
            $criterions = $setting['criterions'];
            $temp[$group] = false;

            $source_description = $this->describe_by_group($criterions, $source_cell);
            $res = $this->compare_counter($source_description,
                $this->describe_by_group($criterions, $response_cell));

            if ($res == 1) {
                $temp[$group] = true;
                $correct++;
                if ($template_cell && $this->compare_counter($source_description,
                        $this->describe_by_group($criterions, $template_cell)) == 1) {
                    $exclude++;
                }
            }
        }
        if ($exclude === count($temp))
            return false;
        $counter['total']++;
        foreach ($temp as $group => $bool) {
            if ($bool)
                $counter[$group]++;
        }
        return $correct !== count($temp);
    }

    /** @param $cell Cell */
    function wrapper($cell, $function) {
        if (!$cell)
            return array();
        try {
            return call_user_func(array(CellCompareCriterions::class, $function), $cell);
        } catch (Exception $ex) {
            return array();
        }
    }
}

class CellCompareCriterions {
    /** Visibility means filter visibility
     * @param $cell Cell
     */
    static function get_visibility($cell) {
        $row_dimension = $cell->getWorksheet()->getRowDimension($cell->getRow());
        $column_dimension = $cell->getWorksheet()->getColumnDimension($cell->getColumn());
        return $row_dimension && $column_dimension ? $row_dimension->getVisible()
            && $column_dimension->getVisible() : array();
    }

    /** @param $cell Cell */
    static function get_mergerange($cell) {
        return $cell->getMergeRange();
    }

    /** @param $cell Cell */
    static function get_value($cell) {
        return $cell->getValue();
    }

    /** @param $cell Cell */
    static function get_calculated_value($cell) {
        return $cell->getCalculatedValue();
    }

    /** @param $cell Cell */
    static function get_bold($cell) {
        return $cell->getStyle()->getFont()->getBold();
    }

    /** @param $cell Cell */
    static function get_fillcolor($cell) {
        return $cell->getStyle()->getFill()->getStartColor()->getARGB();
    }

    /** @param $cell Cell */
    static function get_numberformat($cell) {
        return $cell->getStyle()->getNumberFormat()->getFormatCode();
    }

    /** @param $cell Cell */
    static function get_font_description($cell) {
        return self::describe_font($cell);
    }

    /** @param $cell Cell */
    static function describe_font($cell) {
        $description = array();
        $font = $cell->getStyle()->getFont();
        $description['name'] = $font->getName();
        $description['size'] = $font->getSize();
        $description['underline'] = $font->getUnderline();
        $description['color'] = $font->getColor()->getARGB();
        $description['italic'] = $font->getItalic();
        return $description;
    }
}

class ChartDescriber extends Describer {
    function get_settings($data) {
        $res = array();
        if ($data->groupthreecoef) {
            $items = array();
            if ($data->groupthreeparamone)
                $items['type'] = 'get_plot_type';
            if ($data->groupthreeparamtwo)
                $items['plot_values'] = 'get_plot_values';
            if ($data->groupthreeparamthree)
                $items['axis_x_values'] = 'get_plot_label';
            if ($data->groupthreeparamfour)
                $items['legend_values'] = 'get_plot_category';
            $res[] = array('group' => 'chart', 'coef' => $data->groupthreecoef,
                'criterions' => $items);
        }
        return $res;
    }

    /**
     * @param $writer PhpOffice\PhpSpreadsheet\Writer\IWriter
     */
    public function compare_sheets($data, &$result, $sheet_source, &$sheet_response, &$writer = null) {
        $settings = $this->get_settings($data);
        $mistakes = array();
        if (!empty($settings)) {
            $writer->setIncludeCharts(true);
            foreach ($sheet_source->getChartNames() as $chart_name) {
                $errors = $this->compare_charts($settings, $sheet_source->getChartByName($chart_name),
                    $sheet_response->getChartByName($chart_name), $result);
                if (!empty($errors)) {
                    $mistakes[$chart_name] = $errors;
                }
            }
        }
        return $mistakes;
    }

    /**
     * @param mixed $source_chart {@link Chart} or false
     * @param mixed $response_chart {@link Chart} or false
     */
    function compare_charts($settings, $source_chart, $response_chart, &$result) {
        $errors = array();
        foreach ($settings as $setting) {
            $criterions = $setting['criterions'];
            $res = $this->compare_counter($this->describe_by_group($criterions, $source_chart),
                $this->describe_by_group($criterions, $response_chart), $errors);
            $result[$setting['group']] = $setting['coef'] * $res / 100;
        }
        return $errors;
    }

    /** @param $chart Chart or {@link false} */
    function wrapper($chart, $function) {
        if (!$chart || !($plot_area = $chart->getPlotArea()))
            return array();
        try {
            return call_user_func(array(ChartCompareCriterions::class, $function), $plot_area->getPlotGroup());
        } catch (Exception $ex) {
            return array();
        }
    }
}

class ChartCompareCriterions {

    static function get_plot_type(array $dataseries) {
        $result = array();
        foreach ($dataseries as $index => $dataSeries) {
            $result[$index] = $dataSeries->getPlotType();
        }
        return $result;
    }

    static function get_plot_values(array $dataseries) {
        return self::get_plot_dataSeriesValues($dataseries, 'getPlotValues');
    }

    static function get_plot_label(array $dataseries) {
        return self::get_plot_dataSeriesValues($dataseries, 'getPlotLabels', true);
    }

    static function get_plot_category(array $dataseries) {
        return self::get_plot_dataSeriesValues($dataseries, 'getPlotCategories', true);
    }

    static function get_plot_dataSeriesValues(array $dataseries, $function, $sort = false) {
        $result = array();
        foreach ($dataseries as $index => $dataSeries) {
            $values = array();
            foreach (call_user_func(array($dataSeries, $function)) as $key => $dataSeriesValues) {
                $data = $dataSeriesValues->getDataValues();
                if ($sort) {
                    sort($data);
                }
                $values[$key] = $data;
            }
            $result[$index] = $values;
        }
        return $result;
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