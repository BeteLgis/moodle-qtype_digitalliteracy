<?php

use PhpOffice\PhpWord\Element\ListItem;
use PhpOffice\PhpWord\Element\ListItemRun;
use PhpOffice\PhpWord\Element\Table;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Style;
use PhpOffice\PhpWord\Style\Numbering;
use PhpOffice\PhpWord\Style\Section;

class qtype_digitalliteracy_word_tester extends qtype_digitalliteracy_base_tester {

    protected function get_reader_from_extension($filename) {
        switch (strtolower(pathinfo($filename, PATHINFO_EXTENSION))) {
            case 'docx':
                return 'Word2007';
            default:
                return null;
        }
    }

    protected function IOFactory($reader) {
        return IOFactory::createReader($reader);
    }

    public function validate_file() {
        $word = $this->read($this->data->fullpath);
        if (!$word) {
            $this->result->add_error('shellerr_cantread', $this->data->filename);
            return;
        }
        if (count(word_text_criterions::get_text($word)) === 0) {
            $this->result->add_error('shellerr_emtyfile');
            return;
        }
    }

    public function compare_files() {
        $wordSource = $this->read($this->data->sourcepath);
        word_text_criterions::set_style('source', Style::getStyles());
        Style::resetStyles();

        $wordResponse = $this->read($this->data->responsepath);
        word_text_criterions::set_style('response', Style::getStyles());
        Style::resetStyles();

        $wordTemplate = isset($this->data->templatepath) ?
            $this->read($this->data->templatepath) : null;
        word_text_criterions::set_style('template', Style::getStyles());
        Style::resetStyles();

        list($fraction, $files) = $this->compare_with_coefficients($wordResponse,
            $wordSource, $wordResponse, $wordTemplate);

        $this->result->set_fraction($fraction);
        if ($this->data->validation)
            return;

        $this->result->set_files($files);
        return;
    }

    /**
     * @param PhpWord $wordResponse
     * @param PhpWord $source
     * @param PhpWord $response
     * @param null|PhpWord $template
     * @return array
     */
    private function compare_with_coefficients($wordResponse, $source, $response, $template = null) {
        $result = array(); // contains a mark for each comparison group
        $files = array(); // file name => path for each mistake file

        $wordDescriber = new word_describer();
        if (!empty($mistakes = $wordDescriber->compare_documents($this->data, $result,
                $source, $response, $template))) {
            $name = 'Mistakes.txt';
            $path = $this->data->requestdirectory . '/' . $name;
            file_put_contents($path, implode(PHP_EOL, $mistakes), FILE_APPEND);
            $files[$name] = $path;
        }

        $res = array_sum($result);
        if ($res != 1 && !$this->data->validation) {
            $mistakesname = 'Mistakes_' . $this->data->mistakesname;
            $mistakespath = $this->data->requestdirectory . '/' . $mistakesname;

            $writer = IOFactory::createWriter($wordResponse, 'Word2007');
            $writer->save($mistakespath);
            $files[$mistakesname] = $mistakespath;
        }

        // computing final mark
        return array($res, $files);
    }
}

class word_describer extends qtype_digitalliteracy_object_describer {
    protected function get_settings($data) {
        $res = array();
        if ($data->group1coef) {
            $items = array();
            if ($data->group1param1)
                $items['text'] = ['get_text'];
            if ($data->group1param2)
                $items['links_text'] = ['get_links_text'];
            if ($data->group1param3)
                $items['lists_text'] = ['get_lists_text'];
            if ($data->group1param4)
                $items['tables_text'] = ['get_tables'];
            $res[] = array('group' => 'text', 'coef' => $data->group1coef,
                'criterions' => $items);
        }
        if ($data->group2coef) {
            $items = array();
            if ($data->group2param1)
                $items['text_font'] = ['get_text_font', $data->fontparams];
            if ($data->group2param2)
                $items['links_font'] = ['get_links_font', $data->fontparams];
            if ($data->group2param3)
                $items['lists_style'] = ['get_lists_style', $data->fontparams];
            if ($data->group2param4)
                $items['tables_font'] = ['get_tables', $data->fontparams, false];
            $res[] = array('group' => 'style', 'coef' => $data->group2coef,
                'criterions' => $items);
        }
        if ($data->group3coef) {
            $items = array();
            if ($data->group3param1)
                $items['sections_orientation'] = ['get_sections'];
            if ($data->group3param2)
                $items['sections_margins'] = array('get_sections', 1);
            if ($data->group3param3)
                $items['sections_columns'] = array('get_sections', 2);
            $res[] = array('group' => 'layout', 'coef' => $data->group3coef,
                'criterions' => $items);
        }
        return $res;
    }

    /**
     * @param $data
     * @param $result
     * @param PhpWord $source
     * @param PhpWord $response
     * @param null|PhpWord $template
     */
    public function compare_documents($data, &$result, $source, $response, $template = null) {
        $settings = $this->get_settings($data);
        $mistakeslog = array();
        if (!empty($settings)) {
            foreach ($settings as $setting) {
                $criterions = $setting['criterions'];

                word_text_criterions::set_current('source');
                $sourceDescription = $this->describe_by_group($criterions, $source);
                // Exclude template
                word_text_criterions::set_current('template');
                if ($template && $this->compare_counter($sourceDescription,
                        $this->describe_by_group($criterions, $template)) == 1) {
                    continue;
                }

                word_text_criterions::set_current('response');
                $res = $this->compare_counter($sourceDescription,
                    $this->describe_by_group($criterions, $response), $mistakeslog);
                $result[$setting['group']] = $setting['coef'] * $res / 100;
            }
        }
        return $mistakeslog;
    }

    /** @param PhpWord $document */
    protected function wrapper($document, $function) {
        if (!$document)
            return array();
        try {
            return call_user_func(array(word_text_criterions::class,
                array_shift($function)), $document, ...$function);
        } catch (Exception $ex) {
            return array();
        }
    }
}

class word_text_criterions {
    private static $current = 'source';
    private static $styles = array('source' => [], 'response' => [], 'template' => []);

    public static function set_current($style) {
        self::$current = $style;
    }

    public static function set_style($styleName, $style) {
        self::$styles[$styleName] = $style;
    }

    public static function get_style($styleName) {
        if (isset(self::$styles[self::$current][$styleName])) {
            return self::$styles[self::$current][$styleName];
        }
        return null;
    }

    /** @param PhpWord $document */
    static function get_text($document) {
        $text = '';
        foreach ($document->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                $text .= ' ';
                switch (get_class($element)) {
                    case 'PhpOffice\PhpWord\Element\Title':
                    case 'PhpOffice\PhpWord\Element\Text':
                        $text .= $element->getText();
                        break;
                    case 'PhpOffice\PhpWord\Element\TextRun':
                        foreach ($element->getElements() as $elm) {
                            switch (get_class($elm)) {
                                case 'PhpOffice\PhpWord\Element\Title':
                                case 'PhpOffice\PhpWord\Element\Text':
                                    $text .= $elm->getText();
                                    break;
                            }
                        }
                        break;
                }
            }
        }
        return preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
    }

    /** @param PhpWord $document */
    static function get_text_font($document, $params) {
        $fonts = array();
        foreach ($document->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                switch (get_class($element)) {
                    case 'PhpOffice\PhpWord\Element\Title':
                        self::fonts_push($fonts,
                            self::describe_font($element->getStyle(), $params));
                        break;
                    case 'PhpOffice\PhpWord\Element\Text':
                        self::fonts_push($fonts,
                            self::describe_font($element->getFontStyle(), $params));
                        break;
                    case 'PhpOffice\PhpWord\Element\TextRun':
                        foreach ($element->getElements() as $elm) {
                            switch (get_class($elm)) {
                                case 'PhpOffice\PhpWord\Element\Title':
                                    self::fonts_push($fonts,
                                        self::describe_font($elm->getStyle(), $params));
                                    break;
                                case 'PhpOffice\PhpWord\Element\Text':
                                    self::fonts_push($fonts,
                                        self::describe_font($elm->getFontStyle(), $params));
                                    break;
                            }
                        }
                        break;
                }
            }
        }
        return $fonts;
    }

    /**
     * @param array $fonts
     * @param array $font
     */
    static function fonts_push(&$fonts, $font) {
        if (empty($fonts) || end($fonts) != $font) {
            $fonts[] = $font;
        }
    }

    /** @param null|string|PhpOffice\PhpWord\Style\Font $font */
    static function describe_font($font, $params = "100000") {
        $description = array();
        if (empty($font) || (is_string($font) && !(($font = self::get_style(
                        preg_replace('/Heading/', 'Heading_', $font, 1)))
                    instanceof PhpOffice\PhpWord\Style\Font))) {
            return $description;
        }
        if ($params[0])
            $description['name'] = $font->getName();
        if ($params[1])
            $description['size'] = $font->getSize();
        if ($params[2])
            $description['bold'] = $font->isBold();
        if ($params[3])
            $description['italic'] = $font->isItalic();
        if ($params[4])
            $description['underline'] = $font->getUnderline();
        if ($params[5])
            $description['color'] = $font->getColor();
        return $description;
    }

    /** @param PhpWord $document */
    static function get_links_text($document) {
        $text = '';
        foreach ($document->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                $text .= ' ';
                switch (get_class($element)) {
                    case 'PhpOffice\PhpWord\Element\Link':
                        $text .= $element->getText();
                        break;
                    case 'PhpOffice\PhpWord\Element\TextRun':
                        foreach ($element->getElements() as $elm) {
                            switch (get_class($elm)) {
                                case 'PhpOffice\PhpWord\Element\Link':
                                    $text .= $elm->getText();
                                    break;
                            }
                        }
                        break;
                }
            }
        }
        return preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
    }

    /** @param PhpWord $document */
    static function get_links_font($document, $params) {
        $fonts = array();
        foreach ($document->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                switch (get_class($element)) {
                    case 'PhpOffice\PhpWord\Element\Link':
                        self::fonts_push($fonts,
                            self::describe_font($element->getFontStyle(), $params));
                        break;
                    case 'PhpOffice\PhpWord\Element\TextRun':
                        foreach ($element->getElements() as $elm) {
                            switch (get_class($elm)) {
                                case 'PhpOffice\PhpWord\Element\Link':
                                    self::fonts_push($fonts,
                                        self::describe_font($elm->getFontStyle(), $params));
                                    break;
                            }
                        }
                        break;
                }
            }
        }
        return $fonts;
    }

    /** @param PhpWord $document */
    static function get_lists_text($document) {
        $text = '';
        foreach ($document->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                $text .= ' ';
                switch (get_class($element)) {
                    case 'PhpOffice\PhpWord\Element\ListItem':
                        $text .= $element->getText();
                        break;
                    case 'PhpOffice\PhpWord\Element\ListItemRun':
                        foreach ($element->getElements() as $elm) {
                            switch (get_class($elm)) {
                                case 'PhpOffice\PhpWord\Element\Text':
                                    $text .= $elm->getText();
                                    break;
                            }
                        }
                        break;
                }
            }
        }
        return preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
    }

    /** @param PhpWord $document */
    static function get_lists_style($document, $params) {
        $styles = array();
        foreach ($document->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                switch (get_class($element)) {
                    case 'PhpOffice\PhpWord\Element\ListItemRun':
                    case 'PhpOffice\PhpWord\Element\ListItem':
                        $styles[] = self::describe_list($element, $params);
                        break;
                }
            }
        }
        return $styles;
    }

    /**
     * @param ListItemRun|ListItem $list
     */
    static function describe_list($list, $params) {
        $description = array();
        if (empty($style = $list->getStyle())) {
            return $description;
        }
        $description['depth'] = $list->getDepth();
        $description['format'] = self::get_format($style->getNumStyle(), $list->getDepth());

        if ($list instanceof ListItem) {
            $description['font'] = self::describe_font($list->getTextObject()->getFontStyle(), $params);
        } else {
            foreach ($list->getElements() as $elm) {
                switch (get_class($elm)) {
                    case 'PhpOffice\PhpWord\Element\Text':
                        self::fonts_push($description['font'],
                            self::describe_font($elm->getFontStyle(), $params));
                        break;
                }
            }
        }
        return $description;
    }

    static function get_format($numStyle, $depth = 0) {
        $format = '';
        $numStyleObject = self::get_style($numStyle);
        if ($numStyleObject instanceof Numbering) {
            $format = $numStyleObject->getLevels()[$depth]->getFormat() ?? '';
        }
        return $format;
    }

    /**
     * @param PhpWord $document
     * @param string $params
     * @param bool $text
     * @return array
     */
    static function get_tables($document, $params = "100000", $text = true) {
        $tables = array();
        foreach ($document->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                switch (get_class($element)) {
                    case 'PhpOffice\PhpWord\Element\Table':
                        $tables[] = $text ? self::get_tables_text($element) :
                            self::get_tables_font($element, $params);
                        break;
                }
            }
        }
        return $tables;
    }

    /** @param Table $table */
    static function get_tables_text($table) {
        $cells = array();
        foreach ($table->getRows() as $row) {
            foreach ($row->getCells() as $cell) {
                $text = '';
                foreach ($cell->getElements() as $element) {
                    $text .= ' ';
                    switch (get_class($element)) {
                        case 'PhpOffice\PhpWord\Element\Text':
                            $text .= $element->getText();
                            break;
                        case 'PhpOffice\PhpWord\Element\TextRun':
                            foreach ($element->getElements() as $elm) {
                                switch (get_class($elm)) {
                                    case 'PhpOffice\PhpWord\Element\Text':
                                        $text .= $elm->getText();
                                        break;
                                }
                            }
                            break;
                    }
                }
                if (!empty($value = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY)))
                    $cells[] = $value;
            }
        }
        return $cells;
    }

    /** @param Table $table */
    static function get_tables_font($table, $params) {
        $cells = array();
        foreach ($table->getRows() as $row) {
            foreach ($row->getCells() as $cell) {
                $fonts = array();
                $text = '';
                foreach ($cell->getElements() as $element) {
                    switch (get_class($element)) {
                        case 'PhpOffice\PhpWord\Element\Text':
                            $text .= $element->getText();
                            self::fonts_push($fonts,
                                self::describe_font($element->getFontStyle(), $params));
                            break;
                        case 'PhpOffice\PhpWord\Element\TextRun':
                            foreach ($element->getElements() as $elm) {
                                switch (get_class($elm)) {
                                    case 'PhpOffice\PhpWord\Element\Text':
                                        $text .= $elm->getText();
                                        self::fonts_push($fonts,
                                            self::describe_font($elm->getFontStyle(), $params));
                                        break;
                                }
                            }
                            break;
                    }
                }
                if (!empty($value = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY)))
                    $cells[] = $fonts;
            }
        }
        return $cells;
    }

    /** @param PhpWord $document */
    static function get_sections($document, $type = 0) {
        $settings = array();
        foreach ($document->getSections() as $section) {
            $settings[] = self::describe_section($section->getStyle(), $type);
        }
        return $settings;
    }

    /** @param Section $section */
    static function describe_section($section, $type = 0) {
        $description = array();
        if (empty($section))
            return $description;
        switch($type) {
            case 0:
                $description['orientation'] = $section->getOrientation();
                break;
            case 1:
                $description['margin_top'] = $section->getMarginTop();
                $description['margin_right'] = $section->getMarginRight();
                $description['margin_bottom'] = $section->getMarginBottom();
                $description['margin_left'] = $section->getMarginLeft();
                break;
            case 2:
                $description['cols_num'] = $section->getColsNum();
                $description['cols_space'] = $section->getColsSpace();
                break;
        }
        return $description;
    }
}