<?php

defined('MOODLE_INTERNAL') || die();

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Calculation\Calculation;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;

/** Excel (spreadsheet) tester */
class qtype_digitalliteracy_excel_tester extends qtype_digitalliteracy_base_tester {

    public static function get_strings() {
        return array('error_coordinate_394' => get_string('errorsandbox_coordinate_394', 'qtype_digitalliteracy'),
            'error_stringhelper_481' => get_string('errorsandbox_stringhelper_481', 'qtype_digitalliteracy'),
            'error_xlsx_442' => get_string('errorsandbox_stringhelper_481', 'qtype_digitalliteracy'),
            'error_sheetlimit' => get_string('errorsandbox_sheetlimit', 'qtype_digitalliteracy'),
            'error_zerocells' => get_string('errorsandbox_zerocells', 'qtype_digitalliteracy'));
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
            $collection = array_flip($sheet->getCellCollection()->getCoordinates());
            if (count($collection) === 0) {
                $res = new stdClass();
                $res->title = $sheet->getTitle();
                return $this->data->fork ? get_string('error_zerocells', 'qtype_digitalliteracy') :
                    sprintf($this->data->errors['error_zerocells'], $res->title);
            }
            foreach ($collection as $coordinate => $index) {
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
        $filetype = IOFactory::identify($this->data->responsepath);
        $reader = IOFactory::createReader($filetype);
        $reader->setReadEmptyCells(false);
        $this->data->groupthreecoef > 0 ? $reader->setIncludeCharts(true) :
            $reader->setIncludeCharts(false);

        $spreadsheetResponse = $reader->load($this->data->responsepath);
        $spreadsheetSource = $reader->load($this->data->sourcepath);
        $spreadsheetTemplate = isset($this->data->templatepath) ?
            $reader->load($this->data->templatepath) : null;

        Calculation::getInstance($spreadsheetResponse)->disableCalculationCache(); // not needed as we compare in one run
        Calculation::getInstance($spreadsheetSource)->disableCalculationCache();
        if ($spreadsheetTemplate) {
            Calculation::getInstance($spreadsheetTemplate)->disableCalculationCache();
        }

        $sheetSource = $spreadsheetSource->getSheet(0);
        $sheetResponse = $spreadsheetResponse->getSheet(0);
        $sheetTemplate = $spreadsheetTemplate ? $spreadsheetTemplate->getSheet(0) : null;

        $writer = IOFactory::createWriter($spreadsheetResponse, $filetype);
        list($fraction, $files) = $this->compare_with_coefficients($sheetSource,
            $sheetResponse, $sheetTemplate, $writer);

        if ($this->data->validation)
            return array('fraction' => $fraction);

        return array('files' => $files, 'fraction' => $fraction);
    }

    /**
     * Compares two worksheets using {@link qtype_digitalliteracy_object_describer}.
     * @param Worksheet\Worksheet $source
     * @param Worksheet\Worksheet $response passed by reference as we mark mistakes there!
     * @param Worksheet\Worksheet $template
     * @param PhpOffice\PhpSpreadsheet\Writer\IWriter $writer
     * @return array array of fraction and mistake files
     */
    private function compare_with_coefficients($source, &$response, $template, $writer) {
        $result = array(); // contains a mark for each comparison group
        $files = array(); // file name => path for each mistake file

        $cellDescriber = new excel_cell_describer();
        $cellDescriber->compare_sheets($this->data, $result, $source, $response, $template);

        $chartDescriber = new excel_chart_describer();
        if (!empty($mistakes = $chartDescriber->compare_sheets($this->data, $result,
                $source, $response, $writer)) && !$this->data->validation) {
            $name = 'Mistakes_charts.txt';
            $path = $this->data->requestdirectory . '/' . $name;

            foreach ($mistakes as $chartname => $errors) {
                $str = str_repeat('-', 10);
                file_put_contents($path, $str. 'Chart "'. $chartname.'"' . $str. PHP_EOL, FILE_APPEND);
                file_put_contents($path, implode(PHP_EOL, $errors), FILE_APPEND);
            }
            $files[$name] = $path;
        }

        $res = array_sum($result);
        if ($res != 1 && !$this->data->validation) {
            $mistakesname = 'Mistakes_' . $this->data->mistakesname;
            $mistakespath = $this->data->requestdirectory . '/' . $mistakesname;
            $writer->setUseDiskCaching(true, $this->data->requestdirectory);
            $writer->setPreCalculateFormulas(false);
            $writer->save($mistakespath);
            $files[$mistakesname] = $mistakespath;
        }

        // computing final mark
        return array($res, $files);
    }
}

class excel_cell_describer extends qtype_digitalliteracy_object_describer {
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
     * Compares sheets by Cells (in accordance with {@link excel_cell_describer::get_settings()}).
     * Important we compare cell by cell not cell collections even though
     * it is possible to compare cell collections by implementing CellCollectionDescriber.
     * @param stdClass $data
     * @param Worksheet\Worksheet $source
     * @param Worksheet\Worksheet $response passed by reference as we mark mistakes there!
     * @param Worksheet\Worksheet $template
     * @param array $result group => fraction pairs
     */
    public function compare_sheets($data, &$result, $source, &$response, $template = null) {
        $settings = $this->get_settings($data);
        if (!empty($settings)) {
            $temp = $source->getCellCollection()->getCoordinates();
            $temp1 = $response->getCellCollection()->getCoordinates();
            $collection = array_merge(array_flip($temp), array_flip($temp1));

            $counter = array();
            foreach ($settings as $setting) {
                $counter[$setting['group']] = 0;
            }
            $counter['total'] = 0;

            foreach ($collection as $coordinate => $index) {
                $sourceCell = $source->getCell($coordinate, false);
                $responseCell = $response->getCell($coordinate, true);
                $templateCell = $template ? $template->getCell($coordinate, false) : null;

                // if cells are not equal, we fill cell's background with red in response file
                if ($this->compare_cells($settings, $sourceCell,
                        $responseCell, $templateCell, $counter) && !$data->validation)
                    $responseCell->getStyle()->getFill()->setFillType(
                        \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('ff0000');
            }

            $total = $counter['total'] !== 0 ? $counter['total'] : 1;
            foreach ($settings as $setting) {
                $group = $setting['group'];
                $result[$group] = $setting['coef'] * $counter[$group] / $total / 100;
            }
        }
    }

    /**
     * Compares cells.
     * @param array $settings {@link excel_cell_describer::get_settings()}
     * @param Cell $source
     * @param Cell $response
     * @param Cell $template
     * @param array $counter contains cell match count for each group and total cell count
     * @return bool cells are equal [all criterions match] or not
     */
    function compare_cells($settings, $source, $response, $template, &$counter) {
        $exclude = 0;
        $correct = 0;
        $temp = array();
        foreach ($settings as $setting) {
            $group = $setting['group'];
            $criterions = $setting['criterions'];
            $temp[$group] = false;

            // describe a cell in accordance with criterions for each group
            // and run recursive comparison [compare_counter()]
            $sourceDescription = $this->describe_by_group($criterions, $source);
            $res = $this->compare_counter($sourceDescription,
                $this->describe_by_group($criterions, $response));

            // exclude cell from marking if template is present (which means that
            // it has to be excluded)
            if ($res == 1) {
                $temp[$group] = true;
                $correct++;
                if ($template && $this->compare_counter($sourceDescription,
                        $this->describe_by_group($criterions, $template)) == 1) {
                    $exclude++;
                }
            }
        }
        if ($exclude === count($temp))
            return false;
        $counter['total']++; // we don't count total for each group in excel_cell_describer::get_settings(),
                             // we simply increase count by 1 each iteration
        foreach ($temp as $group => $bool) {
            if ($bool)
                $counter[$group]++;
        }
        return $correct !== count($temp);
    }

    /** @param Cell $cell */
    function wrapper($cell, $function) {
        if (!$cell)
            return array();
        try {
            return call_user_func(array(excel_cell_criterions::class, $function), $cell);
        } catch (Exception $ex) {
            return array();
        }
    }
}

class excel_cell_criterions {
    /**
     * Visibility means filter visibility
     * @param Cell $cell
     */
    static function get_visibility($cell) {
        $rowDimension = $cell->getWorksheet()->getRowDimension($cell->getRow());
        $columnDimension = $cell->getWorksheet()->getColumnDimension($cell->getColumn());
        return $rowDimension && $columnDimension ? $rowDimension->getVisible()
            && $columnDimension->getVisible() : array();
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

class excel_chart_describer extends qtype_digitalliteracy_object_describer {
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
     * Compares sheets by Charts (in accordance with {@link excel_chart_describer::get_settings()}).
     * @param stdClass $data
     * @param array $result group => fraction pairs
     * @param Worksheet\Worksheet $source
     * @param Worksheet\Worksheet $response
     * @param PhpOffice\PhpSpreadsheet\Writer\IWriter $writer
     * @return array array of mistakes for each chart (chart name => errors)
     */
    public function compare_sheets($data, &$result, $source, &$response, &$writer) {
        $settings = $this->get_settings($data);
        $mistakes = array();
        if (!empty($settings)) {
            $writer->setIncludeCharts(true);
            foreach ($source->getChartNames() as $name) {
                $mistakeslog = $this->compare_charts($settings, $source->getChartByName($name),
                    $response->getChartByName($name), $result);
                if (!empty($mistakeslog)) {
                    $mistakes[$name] = $mistakeslog;
                }
            }
        }
        return $mistakes;
    }

    /**
     * Compares charts.
     * @param array $settings {@link excel_chart_describer::get_settings()}
     * @param Chart $source
     * @param Chart $response
     * @param array $result group => fraction pairs
     * @return array array of mistakes
     */
    function compare_charts($settings, $source, $response, &$result) {
        $mistakeslog = array();
        foreach ($settings as $setting) {
            $criterions = $setting['criterions'];
            $res = $this->compare_counter($this->describe_by_group($criterions, $source),
                $this->describe_by_group($criterions, $response), $mistakeslog);
            $result[$setting['group']] = $setting['coef'] * $res / 100;
        }
        return $mistakeslog;
    }

    /** @param Chart $chart */
    function wrapper($chart, $function) {
        if (!$chart || !($plotArea = $chart->getPlotArea()))
            return array();
        try {
            return call_user_func(array(excel_chart_criterions::class, $function), $plotArea->getPlotGroup());
        } catch (Exception $ex) {
            return array();
        }
    }
}

class excel_chart_criterions {

    /** @param DataSeries[] $dataseries */
    static function get_plot_type($dataseries) {
        $result = array();
        foreach ($dataseries as $index => $dataSeries) {
            $result[$index] = $dataSeries->getPlotType();
        }
        return $result;
    }

    /** @param DataSeries[] $dataseries */
    static function get_plot_values($dataseries) {
        return self::get_plot_dataSeriesValues($dataseries, 'getPlotValues');
    }

    /** @param DataSeries[] $dataseries */
    static function get_plot_label($dataseries) {
        return self::get_plot_dataSeriesValues($dataseries, 'getPlotLabels', true);
    }

    /** @param DataSeries[] $dataseries */
    static function get_plot_category($dataseries) {
        return self::get_plot_dataSeriesValues($dataseries, 'getPlotCategories', true);
    }

    /**
     * @param DataSeries[] $dataseries
     * @param string $function
     * @param bool $sort
     */
    static function get_plot_dataSeriesValues($dataseries, $function, $sort = false) {
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


/** Can be used for filtered (chunk) read */
class ChunkReadFilter implements \PhpOffice\PhpSpreadsheet\Reader\IReadFilter {
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