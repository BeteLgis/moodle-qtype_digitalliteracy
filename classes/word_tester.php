<?php

use PhpOffice\PhpWord\Element\Text;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Writer\WriterInterface;

class qtype_digitalliteracy_word_tester extends qtype_digitalliteracy_base_tester {

    public function validate_file($result) {
        $reader = IOFactory::createReader('Word2007');
        $word = $reader->load($this->data->fullpath);
        if (count(word_text_criterions::get_text($word)) === 0) {
            $result->add_error('shellerr_emtyfile');
            return;
        }
        return;
    }

    public function compare_files($result) {
        $filetype = 'Word2007';
        $reader = IOFactory::createReader($filetype);
        $wordSource = $reader->load($this->data->responsepath);
        $wordResponse = $reader->load($this->data->sourcepath);
        $wordTemplate = isset($this->data->templatepath) ?
            $reader->load($this->data->templatepath) : null;

        $writer = IOFactory::createWriter($wordResponse,$filetype);
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

        $textDescriber = new word_text_describer();
        $textDescriber->compare_documents($this->data, $result, $wordSource, $wordResponse);

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

class word_text_describer extends qtype_digitalliteracy_object_describer {
    function get_settings($data) {
        $res = array();
        if ($data->grouponecoef) {
            $items = array();
            if ($data->grouponeparamone)
                $items['text'] = 'get_text';
            if ($data->grouponeparamtwo)
                $items['font'] = 'get_font';
            $res[] = array('group' => 'text', 'coef' => $data->grouponecoef,
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
    public function compare_documents($data, &$result, $source, &$response, $template = null) {
        $settings = $this->get_settings($data);
        if (!empty($settings)) {
            foreach ($settings as $setting) {
                $criterions = $setting['criterions'];
                $res = $this->compare_counter($this->describe_by_group($criterions, $source),
                    $this->describe_by_group($criterions, $response));
                $result[$setting['group']] = $setting['coef'] * $res / 100;
            }
        }
    }

    /** @param PhpWord $document */
    function wrapper($document, $function) {
        if (!$document)
            return array();
        try {
            return call_user_func(array(word_text_criterions::class, $function), $document);
        } catch (Exception $ex) {
            return array();
        }
    }
}

class word_text_criterions {
    /** @param PhpWord $document */
    static function get_text($document) {
        $text = '';
        foreach ($document->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                switch (get_class($element)) {
                    case 'PhpOffice\PhpWord\Element\Title':
                    case 'PhpOffice\PhpWord\Element\Text':
                        $text .= $element->getText();
                        break;
                    case 'PhpOffice\PhpWord\Element\TextRun':
                        $text .= ' ';
                        foreach ($element->getElements() as $elm) {
                            switch (get_class($elm)) {
                                case 'PhpOffice\PhpWord\Element\Title':
                                case 'PhpOffice\PhpWord\Element\Text':
                                    $text .= $elm->getText();
                                    break;
                                default:
                                    $text .= ' ';
                                    break;
                            }
                        }
                        break;
                    default:
                        $text .= ' ';
                        break;
                }
            }
        }
        return preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
    }

    /** @param PhpWord $document */
    static function get_font($document) {
        $fonts = array();
        foreach ($document->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                switch (get_class($element)) {
                    case 'PhpOffice\PhpWord\Element\Title':
                        $fonts[] = $element->getStyle();
                        break;
                    case 'PhpOffice\PhpWord\Element\Text':
                        $fonts[] = self::describe_font($element);
                        break;
                    case 'PhpOffice\PhpWord\Element\TextRun':
                        foreach ($element->getElements() as $elm) {
                            switch (get_class($elm)) {
                                case 'PhpOffice\PhpWord\Element\Title':
                                    $fonts[] = $elm->getStyle();
                                    break;
                                case 'PhpOffice\PhpWord\Element\Text':
                                    $fonts[] = self::describe_font($elm);
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
    static function get_links($document) {
        $fonts = array();
        foreach ($document->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                switch (get_class($element)) {
                    case 'PhpOffice\PhpWord\Element\Title':
                        $fonts[] = $element->getStyle();
                        break;
                    case 'PhpOffice\PhpWord\Element\Text':
                        $fonts[] = self::describe_font($element);
                        break;
                    case 'PhpOffice\PhpWord\Element\TextRun':
                        foreach ($element->getElements() as $elm) {
                            switch (get_class($elm)) {
                                case 'PhpOffice\PhpWord\Element\Title':
                                    $fonts[] = $elm->getStyle();
                                    break;
                                case 'PhpOffice\PhpWord\Element\Text':
                                    $fonts[] = self::describe_font($elm);
                                    break;
                            }
                        }
                        break;
                }
            }
        }
        return $fonts;
    }

    /** @param Text $text */
    static function describe_font($text) {
        $description = array();
        if (!($font = $text->getFontStyle()))
            return $description;
        $description['name'] = $font->getName();
        $description['size'] = $font->getSize();
        $description['underline'] = $font->getUnderline();
        $description['color'] = $font->getColor();
        $description['italic'] = $font->isItalic();
        $description['bold'] = $font->isBold();
        return $description;
    }
}