<?php

use PhpOffice\PhpPresentation\Shape\RichText;
use PhpOffice\PhpPresentation\Shape\Table;
use PhpOffice\PhpPresentation\PhpPresentation;
use PhpOffice\PhpPresentation\IOFactory;
use PhpOffice\PhpPresentation\Writer\WriterInterface;

class qtype_digitalliteracy_powerpoint_tester extends qtype_digitalliteracy_base_tester {

    protected function get_reader_from_extension($filename) {
        switch (strtolower(pathinfo($filename, PATHINFO_EXTENSION))) {
            case 'pptx':
                return 'PowerPoint2007';
            case 'ppt':
                return 'PowerPoint97';
            case 'odp':
                return 'ODPresentation';
            default:
                return null;
        }
    }

    protected function IOFactory($reader) {
        return IOFactory::createReader($reader);
    }

    public function validate_file() {
        $presentation = $this->read($this->data->fullpath);
        if (!$presentation) {
            $this->result->add_error('shellerr_cantread', $this->data->filename);
            return;
        }
        if (count($presentation->getAllSlides()) === 0) {
            $this->result->add_error('shellerr_zeroslides');
            return;
        }
    }

    public function compare_files() {
        $ppSource = $this->read($this->data->sourcepath);
        $ppResponse = $this->read($this->data->responsepath);
        $ppTemplate = isset($this->data->templatepath) ?
            $this->read($this->data->templatepath) : null;

        $writer = IOFactory::createWriter($ppSource, 'PowerPoint2007');
        list($fraction, $files) = $this->compare_with_coefficients($ppSource,
            $ppResponse, $ppTemplate, $writer);

        $this->result->set_fraction($fraction);
        if ($this->data->validation)
            return;

        $this->result->set_files($files);
        return;
    }

    /**
     * @param PhpPresentation $ppSource
     * @param PhpPresentation $ppResponse
     * @param PhpPresentation $ppTemplate
     * @param WriterInterface $writer
     */
    private function compare_with_coefficients($ppSource, $ppResponse, $ppTemplate, $writer) {
        $result = array(); // contains a mark for each comparison group
        $files = array(); // file name => path for each mistake file

        $ppDescriber = new powerpoint_describer();
        if (!empty($mistakes = $ppDescriber->compare_presentations($this->data, $result,
            $ppSource, $ppResponse))) {
            $name = 'Mistakes.txt';
            $path = $this->data->requestdirectory . '/' . $name;
            file_put_contents($path, implode(PHP_EOL, $mistakes), FILE_APPEND);
            $files[$name] = $path;
        }

        $res = array_sum($result);
        if ($res != 1 && !$this->data->validation) {
            $mistakesname = 'Mistakes_' . $this->data->mistakesname;
            $mistakespath = $this->data->requestdirectory . '/' . $mistakesname;
            $writer->save($mistakespath);
            $files[$mistakesname] = $mistakespath;
        }

        // computing final mark
        return array($res, $files);
    }
}

class powerpoint_describer extends qtype_digitalliteracy_object_describer {

    protected function get_settings($data) {
        $res = array();
        if ($data->group1coef) {
            $items = array();
            if ($data->group1param1)
                $items['text'] = ['get_text'];
            if ($data->group1param2)
                $items['tables_text'] = ['get_tables_text'];
            $res[] = array('group' => 'text', 'coef' => $data->group1coef,
                'criterions' => $items);
        }
        return $res;
    }

    /**
     * @param $data
     * @param $result
     * @param PhpPresentation $source
     * @param PhpPresentation $response
     * @param PhpPresentation $template
     */
    public function compare_presentations($data, &$result, $source, $response, $template = null) {
        $settings = $this->get_settings($data);
        $mistakeslog = array();
        if (!empty($settings)) {
            foreach ($settings as $setting) {
                $criterions = $setting['criterions'];
                $source_description = $this->describe_by_group($criterions, $source);
                $response_description = $this->describe_by_group($criterions, $response);
                $res = $this->compare_counter($source_description, $response_description, $mistakeslog);
                $result[$setting['group']] = $setting['coef'] * $res / 100;
            }
        }
        return $mistakeslog;
    }

    /** @param PhpPresentation $presentation */
    protected function wrapper($presentation, $function) {
        if (!$presentation)
            return array();
        try {
            return call_user_func(array(powerpoint_criterions::class,
                array_shift($function)), $presentation, ...$function);
        } catch (Exception $ex) {
            return array();
        }
    }
}

class powerpoint_criterions {

    /** @param PhpPresentation $presentation */
    static function get_text($presentation) {
        $text = '';
        foreach ($presentation->getAllSlides() as $slide) {
            foreach ($slide->getShapeCollection() as $shape) {
                if ($shape instanceof RichText) {
                    $text .= self::get_plain_text($shape);
                }
            }
        }
        return preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
    }

    /** @param RichText|Table\Cell $shape */
    static function get_plain_text($shape) {
        $text = '';
        foreach ($shape->getParagraphs() as $paragraph) {
            foreach ($paragraph->getRichTextElements() as $txt) {
                $text .= ' ';
                if ($txt instanceof RichText\TextElementInterface) {
                    $text .= $txt->getText();
                }
            }
        }
        return $text;
    }

    /** @param PhpPresentation $presentation */
    static function get_tables_text($presentation) {
        $text = '';
        foreach ($presentation->getAllSlides() as $slide) {
            foreach ($slide->getShapeCollection() as $shape) {
                if ($shape instanceof Table) {
                    foreach ($shape->getRows() as $row) {
                        foreach ($row->getCells() as $cell) {
                            $text .= self::get_plain_text($cell);
                        }
                    }
                }
            }
        }
        return preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
    }
}