<?php

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Calculation\Calculation;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;

/** Excel (spreadsheet) tester */
class qtype_digitalliteracy_excel_tester extends qtype_digitalliteracy_base_tester {

    protected function get_reader_from_extension($filename) {
        switch (strtolower(pathinfo($filename, PATHINFO_EXTENSION))) {
            case 'xlsx':
                return 'Xlsx';
            case 'xls':
                return 'Xls';
            case 'ods':
                return 'Ods';
            default:
                return null;
        }
    }

    protected function IOFactory($reader) {
        return IOFactory::createReader($reader);
    }

    /** @param bool $includeCharts */
    protected function set_config($includeCharts) {
        $this->config->includeCharts = $includeCharts;
    }

    /** @param \PhpOffice\PhpSpreadsheet\Reader\IReader $reader */
    protected function reader_apply_config($reader) {
        $reader->setReadEmptyCells(false);
        $this->config->includeCharts ? $reader->setIncludeCharts(true) :
            $reader->setIncludeCharts(false);
    }

    public function validate_file() {
        $this->set_config(true);
        $spreadsheet = $this->read($this->data->fullpath);
        if (!$spreadsheet) {
            $this->result->add_error('shellerr_cantread', $this->data->filename);
            return;
        }
        Calculation::getInstance($spreadsheet)->disableCalculationCache();
        if (($count = $spreadsheet->getSheetCount()) !== 1) {
            $this->result->add_error('shellerr_sheetlimit');
            return;
        }
        $sheet = $spreadsheet->getSheet(0);
        $collection = array_flip($sheet->getCellCollection()->getCoordinates());
        if (count($collection) === 0) {
            $this->result->add_error('shellerr_zerocells', $sheet->getTitle());
            return;
        }
        foreach ($collection as $coordinate => $index) {
            $cell = $sheet->getCell($coordinate, false);
            $cell->getCalculatedValue(); // looking for infinite loops (like 'F:F' range)
        }
    }

    public function compare_files() {
        $this->set_config($this->data->group3coef > 0);
        $spreadsheetSource = $this->read($this->data->sourcepath);
        $spreadsheetResponse = $this->read($this->data->responsepath);
        $spreadsheetTemplate = isset($this->data->templatepath) ?
            $this->read($this->data->templatepath) : null;

        Calculation::getInstance($spreadsheetResponse)->disableCalculationCache(); // not needed as we compare in one run
        Calculation::getInstance($spreadsheetSource)->disableCalculationCache();
        if ($spreadsheetTemplate) {
            Calculation::getInstance($spreadsheetTemplate)->disableCalculationCache();
        }

        $sheetSource = $spreadsheetSource->getSheet(0);
        $sheetResponse = $spreadsheetResponse->getSheet(0);
        $sheetTemplate = $spreadsheetTemplate ? $spreadsheetTemplate->getSheet(0) : null;

        list($fraction, $files) = $this->compare_with_coefficients($spreadsheetResponse,
            $sheetSource, $sheetResponse, $sheetTemplate);

        $this->result->set_fraction($fraction);
        if ($this->data->validation)
            return;

        $this->result->set_files($files);
        return;
    }

    /**
     * Compares two worksheets using {@link qtype_digitalliteracy_object_describer}.
     * @param Spreadsheet $spreadsheetResponse
     * @param Worksheet\Worksheet $source
     * @param Worksheet\Worksheet $response
     * @param null|Worksheet\Worksheet $template
     * @return array array of fraction and mistake files
     */
    private function compare_with_coefficients($spreadsheetResponse, $source, $response, $template = null) {
        $result = array(); // contains a mark for each comparison group
        $files = array(); // file name => path for each mistake file

        $cellDescriber = new excel_cell_describer();
        $cellDescriber->compare_sheets($this->data, $result, $source, $response, $template);

        $chartDescriber = new excel_chart_describer();
        if (!empty($chartMistakes = $chartDescriber->compare_sheets($this->data, $result,
                $source, $response, $template)) && !$this->data->validation) {
            $name = 'Mistakes_charts.txt';
            $path = $this->data->requestdirectory . '/' . $name;

            foreach ($chartMistakes as $chartname => $errors) {
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

            $writer = IOFactory::createWriter($spreadsheetResponse, 'Xlsx');
            if (!empty($chartMistakes)) {
                $writer->setIncludeCharts(true);
            }
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
    protected function get_settings($data) {
        $res = array();
        if ($data->group1coef) {
            $items = array();
            if ($data->group1param1)
                $items['value'] = ['get_value'];
            if ($data->group1param2)
                $items['calculated_value'] = ['get_calculated_value'];
            if ($data->group1param3)
                $items['visibility'] = ['get_visibility'];
            if ($data->group1param4)
                $items['mergerange'] = ['get_mergerange'];
            $res[] = array('group' => 'value','coef' => $data->group1coef,
                'criterions' => $items);
        }
        if ($data->group2coef) {
            $items = array();
            if ($data->group2param1)
                $items['font'] = ['get_font', $data->fontparams];
            if ($data->group2param2)
                $items['fillcolor'] = ['get_fillcolor'];
            if ($data->group2param3)
                $items['numberformat'] = ['get_numberformat'];
            if ($data->group2param4)
                $items['alignment'] = ['get_alignment'];
            $res[] = array('group' => 'style','coef' => $data->group2coef,
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
     * @param Worksheet\Worksheet $response
     * @param null|Worksheet\Worksheet $template
     * @param array $result group => fraction pairs
     */
    public function compare_sheets($data, &$result, $source, $response, $template = null) {
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
                if ($this->compare_cells($counter, $settings,
                        $sourceCell, $responseCell, $templateCell) && !$data->validation)
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
     * @param array $counter contains cell match count for each group and total cell count
     * @param array $settings {@link excel_cell_describer::get_settings()}
     * @param Cell $source
     * @param Cell $response
     * @param null|Cell $template
     * @return bool cells are equal [all criterions match] or not
     */
    private function compare_cells(&$counter, $settings, $source, $response, $template = null) {
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

            // exclude cell from marking if a template is present (which means that
            // it has to be excluded)
            if ($template && $this->compare_counter($sourceDescription,
                    $this->describe_by_group($criterions, $template)) == 1) {
                $exclude++;
                continue;
            }

            if ($this->compare_counter($sourceDescription,
                    $this->describe_by_group($criterions, $response)) == 1) {
                $temp[$group] = true;
                $correct++;
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
    protected function wrapper($cell, $function) {
        if (!$cell)
            return array();
        try {
            return call_user_func(array(excel_cell_criterions::class,
                array_shift($function)), $cell, ...$function);
        } catch (Throwable $ex) {
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
    static function get_alignment($cell) {
        $alignment = array();
        $alignment['horizontal'] = $cell->getStyle()->getAlignment()->getHorizontal();
        $alignment['vertical'] = $cell->getStyle()->getAlignment()->getVertical();
        return $alignment;
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
    static function get_font($cell, $params = "100000") {
        $description = array();
        if (!($style = $cell->getStyle()))
            return $description;
        if (!($font = $style->getFont())) {
            if ($params[0])
                $description['name'] = $font->getName();
            if ($params[1])
                $description['size'] = $font->getSize();
            if ($params[2])
                $description['bold'] = $font->getBold();
            if ($params[3])
                $description['italic'] = $font->getItalic();
            if ($params[4])
                $description['underline'] = $font->getUnderline();
            if ($params[5])
                $description['color'] = $font->getColor()->getARGB();
        }
        return $description;
    }
}

class excel_chart_describer extends qtype_digitalliteracy_object_describer {
    protected function get_settings($data) {
        $res = array();
        if ($data->group3coef) {
            $items = array();
            if ($data->group3param1)
                $items['type'] = 'get_plot_type';
            if ($data->group3param2)
                $items['plot_values'] = 'get_plot_values';
            if ($data->group3param3)
                $items['axis_x_values'] = 'get_plot_label';
            if ($data->group3param4)
                $items['legend_values'] = 'get_plot_category';
            $res[] = array('group' => 'chart', 'coef' => $data->group3coef,
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
     * @param null|Worksheet\Worksheet $template
     * @return array array of mistakes for each chart (chart name => errors)
     */
    public function compare_sheets($data, &$result, $source, $response, $template = null) {
        $settings = $this->get_settings($data);
        $mistakes = array();
        if (!empty($settings)) {
            foreach ($source->getChartNames() as $name) {
                $templateChart = $template ? $template->getChartByName($name) : false;
                $mistakeslog = $this->compare_charts($result, $settings,
                    $source->getChartByName($name), $response->getChartByName($name), $templateChart);
                if (!empty($mistakeslog)) {
                    $mistakes[$name] = $mistakeslog;
                }
            }
        }
        return $mistakes;
    }

    /**
     * Compares charts.
     * @param array $result group => fraction pairs
     * @param array $settings {@link excel_chart_describer::get_settings()}
     * @param Chart $source
     * @param Chart $response
     * @param false|Chart $template
     * @return array array of mistakes
     */
    private function compare_charts(&$result, $settings, $source, $response, $template = false) {
        $mistakeslog = array();
        foreach ($settings as $setting) {
            $criterions = $setting['criterions'];

            $sourceDescription = $this->describe_by_group($criterions, $source);
            // Exclude template
            if ($template && $this->compare_counter($sourceDescription,
                    $this->describe_by_group($criterions, $template)) == 1) {
                continue;
            }

            $res = $this->compare_counter($sourceDescription,
                $this->describe_by_group($criterions, $response), $mistakeslog);
            $result[$setting['group']] = $setting['coef'] * $res / 100;
        }
        return $mistakeslog;
    }

    /** @param Chart $chart */
    protected function wrapper($chart, $function) {
        if (!$chart || !($plotArea = $chart->getPlotArea()))
            return array();
        try {
            return call_user_func(array(excel_chart_criterions::class, $function), $plotArea->getPlotGroup());
        } catch (Throwable $ex) {
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