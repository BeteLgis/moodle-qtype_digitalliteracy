<?php

use PhpOffice\PhpWord\Element\ListItem;
use PhpOffice\PhpWord\Element\ListItemRun;
use PhpOffice\PhpWord\Element\Table;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Style;
use PhpOffice\PhpWord\Style\Numbering;
use PhpOffice\PhpWord\Style\Section;
use PhpOffice\PhpWord\Writer\WriterInterface;

class qtype_digitalliteracy_word_tester extends qtype_digitalliteracy_base_tester {

    public function validate_file($result) {
        $reader = IOFactory::createReader('Word2007');
        if (!$reader->canRead($this->data->fullpath)) {
            $result->add_error('shellerr_cantread', $this->data->filename);
            return;
        }

        $word = $reader->load($this->data->fullpath);
        if (count(word_text_criterions::get_text($word)) === 0) {
            $result->add_error('shellerr_emtyfile');
            return;
        }
    }

    public function compare_files($result) {
        $filetype = 'Word2007';
        $reader = IOFactory::createReader($filetype);

        $wordSource = $reader->load($this->data->sourcepath);
        word_text_criterions::set_style('source', Style::getStyles());
        Style::resetStyles();

        $wordResponse = $reader->load($this->data->responsepath);
        word_text_criterions::set_style('response', Style::getStyles());
        Style::resetStyles();

        $wordTemplate = isset($this->data->templatepath) ?
            $reader->load($this->data->templatepath) : null;

        $writer = IOFactory::createWriter($wordSource, $filetype);
        list($fraction, $files) = $this->compare_with_coefficients($wordSource,
            $wordResponse, $wordTemplate, $writer);

        $result->set_fraction($fraction);
        if ($this->data->validation)
            return;

        $result->set_files($files);
        return;
    }

    /**
     * @param PhpWord $wordSource
     * @param PhpWord $wordResponse
     * @param PhpWord $wordTemplate
     * @param WriterInterface $writer
     */
    private function compare_with_coefficients($wordSource, $wordResponse, $wordTemplate, $writer) {
        $result = array(); // contains a mark for each comparison group
        $files = array(); // file name => path for each mistake file

        $wordDescriber = new word_describer();
        if (!empty($mistakes = $wordDescriber->compare_documents($this->data, $result,
            $wordSource, $wordResponse))) {
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

class word_describer extends qtype_digitalliteracy_object_describer {
    function get_settings($data) {
        $res = array();
        if ($data->grouponecoef) {
            $items = array();
            if ($data->grouponeparamone)
                $items['text'] = ['get_text'];
            if ($data->grouponeparamtwo)
                $items['links_text'] = ['get_links_text'];
            if ($data->grouponeparamthree)
                $items['lists_text'] = ['get_lists_text'];
            if ($data->grouponeparamfour)
                $items['tables_text'] = ['get_tables'];
            $res[] = array('group' => 'text', 'coef' => $data->grouponecoef,
                'criterions' => $items);
        }
        if ($data->grouptwocoef) {
            $items = array();
            if ($data->grouptwoparamone)
                $items['text_font'] = ['get_text_font'];
            if ($data->grouptwoparamtwo)
                $items['links_font'] = ['get_links_font'];
            if ($data->grouptwoparamthree)
                $items['lists_style'] = ['get_lists_style'];
            if ($data->grouptwoparamfour)
                $items['tables_font'] = array('get_tables', false);
            $res[] = array('group' => 'style', 'coef' => $data->grouptwocoef,
                'criterions' => $items);
        }
        if ($data->groupthreecoef) {
            $items = array();
            if ($data->groupthreeparamone)
                $items['sections_orientation'] = ['get_sections'];
            if ($data->groupthreeparamtwo)
                $items['sections_margins'] = array('get_sections', 1);
            if ($data->groupthreeparamthree)
                $items['sections_columns'] = array('get_sections', 2);
            $res[] = array('group' => 'layout', 'coef' => $data->groupthreecoef,
                'criterions' => $items);
        }
        return $res;
    }

    /**
     * @param $data
     * @param $result
     * @param PhpWord $source
     * @param PhpWord $response
     * @param PhpWord $template
     */
    public function compare_documents($data, &$result, $source, $response, $template = null) {
        $settings = $this->get_settings($data);
        $mistakeslog = array();
        if (!empty($settings)) {
            foreach ($settings as $setting) {
                $criterions = $setting['criterions'];
                word_text_criterions::set_current('source');
                $source_description = $this->describe_by_group($criterions, $source);
                word_text_criterions::set_current('response');
                $response_description = $this->describe_by_group($criterions, $response);
                $res = $this->compare_counter($source_description, $response_description, $mistakeslog);
                $result[$setting['group']] = $setting['coef'] * $res / 100;
            }
        }
        return $mistakeslog;
    }

    /** @param PhpWord $document */
    function wrapper($document, $function) {
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
    private static $styles = array('source' => [], 'response' => []);

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
    static function get_text_font($document, $type = true) {
        $fonts = array();
        foreach ($document->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                switch (get_class($element)) {
                    case 'PhpOffice\PhpWord\Element\Title':
                        self::fonts_push($fonts,
                            self::describe_font($element->getStyle(), $type));
                        break;
                    case 'PhpOffice\PhpWord\Element\Text':
                        self::fonts_push($fonts,
                            self::describe_font($element->getFontStyle(), $type));
                        break;
                    case 'PhpOffice\PhpWord\Element\TextRun':
                        foreach ($element->getElements() as $elm) {
                            switch (get_class($elm)) {
                                case 'PhpOffice\PhpWord\Element\Title':
                                    self::fonts_push($fonts,
                                        self::describe_font($elm->getStyle(), $type));
                                    break;
                                case 'PhpOffice\PhpWord\Element\Text':
                                    self::fonts_push($fonts,
                                        self::describe_font($elm->getFontStyle(), $type));
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
    static function describe_font($font, $type = true) {
        $description = array();
        if (empty($font) || (is_string($font) && !(($font = self::get_style(
                        preg_replace('/Heading/', 'Heading_', $font, 1)))
                    instanceof PhpOffice\PhpWord\Style\Font))) {
            return $description;
        }
        if ($type) {
            $description['italic'] = $font->isItalic();
            $description['bold'] = $font->isBold();
            $description['underline'] = $font->getUnderline();
        } else {
            $description['name'] = $font->getName();
            $description['size'] = $font->getSize();
            $description['color'] = $font->getColor();
            $description['fgColor'] = $font->getFgColor();
        }
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
    static function get_links_font($document, $type = true) {
        $fonts = array();
        foreach ($document->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                switch (get_class($element)) {
                    case 'PhpOffice\PhpWord\Element\Link':
                        self::fonts_push($fonts,
                            self::describe_font($element->getFontStyle(), $type));
                        break;
                    case 'PhpOffice\PhpWord\Element\TextRun':
                        foreach ($element->getElements() as $elm) {
                            switch (get_class($elm)) {
                                case 'PhpOffice\PhpWord\Element\Link':
                                    self::fonts_push($fonts,
                                        self::describe_font($elm->getFontStyle(), $type));
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
    static function get_lists_style($document, $type = true) {
        $styles = array();
        foreach ($document->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                switch (get_class($element)) {
                    case 'PhpOffice\PhpWord\Element\ListItemRun':
                    case 'PhpOffice\PhpWord\Element\ListItem':
                        $styles[] = self::describe_list($element, $type);
                        break;
                }
            }
        }
        return $styles;
    }

    /**
     * @param ListItemRun|ListItem $list
     */
    static function describe_list($list, $type) {
        $description = array();
        if (empty($style = $list->getStyle())) {
            return $description;
        }
        $description['depth'] = $list->getDepth();
        $description['format'] = self::get_format($style->getNumStyle(), $list->getDepth());

        if ($list instanceof ListItem) {
            $description['font'] = self::describe_font($list->getTextObject()->getFontStyle(), $type);
        } else {
            foreach ($list->getElements() as $elm) {
                switch (get_class($elm)) {
                    case 'PhpOffice\PhpWord\Element\Text':
                        self::fonts_push($description['font'],
                            self::describe_font($elm->getFontStyle(), $type));
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

    /** @param PhpWord $document */
    static function get_tables($document, $text = true, $type = true) {
        $tables = array();
        foreach ($document->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                switch (get_class($element)) {
                    case 'PhpOffice\PhpWord\Element\Table':
                        $tables[] = $text ? self::get_tables_text($element) :
                            self::get_tables_font($element, $type);
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
    static function get_tables_font($table, $type = true) {
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
                                self::describe_font($element->getFontStyle(), $type));
                            break;
                        case 'PhpOffice\PhpWord\Element\TextRun':
                            foreach ($element->getElements() as $elm) {
                                switch (get_class($elm)) {
                                    case 'PhpOffice\PhpWord\Element\Text':
                                        $text .= $elm->getText();
                                        self::fonts_push($fonts,
                                            self::describe_font($elm->getFontStyle(), $type));
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