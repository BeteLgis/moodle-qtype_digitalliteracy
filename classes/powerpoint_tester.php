<?php
use PhpOffice\PhpPresentation\Style\Fill;
use PhpOffice\PhpPresentation\PhpPresentation;
use PhpOffice\PhpPresentation\IOFactory;
use PhpOffice\PhpPresentation\Style\Color;
use PhpOffice\PhpPresentation\Style\Alignment;


class qtype_digitalliteracy_powerpoint_tester implements qtype_digitalliteracy_compare_interface
{
    public function validate_file($filepath, $filename) {
        //TODO
        return '';
    }

    public function compare_files(&$data)
    {
        $samplePptx = IOFactory::load($data->response_path);
        $analysPptx = IOFactory::load($data->source_path);

        $cmpSlides = $this->compareSlidesCount($samplePptx, $analysPptx);
        $analysText = $this->testFontsText($samplePptx, $analysPptx);
        $cmpShapes = $this->compareShapes($samplePptx, $analysPptx);
        $cmpLayouts = $this->compareLayout($samplePptx, $analysPptx);
        $scored = $cmpShapes[0] + $cmpSlides[0] + $analysText[0] + $cmpLayouts[0];
        $max = $cmpShapes[1] + $cmpSlides[1] + $analysText[1] + $cmpLayouts[1];
        if ($max == 0)
            $max = 1;
        $fraction = $scored/$max;
        if ($data->flag)
            return array('fraction' => $fraction);
        $mistakes_name = 'Mistakes_' . $data->mistakes_name;
        $mistakes_path = $data->request_directory . '\\' . $mistakes_name;
        $writer = IOFactory::createWriter($samplePptx);
        $writer->save($mistakes_path);
        return array('file_saver' => qtype_digitalliteracy_comparator::
        generate_question_file_saver($mistakes_name, $mistakes_path), 'fraction' => $fraction);
    }

    private function getSlidesArray($pptx){
        $slides = array();
        foreach($pptx->getAllSlides() as $slide){
            array_push($slides, $slide);
        }
        return $slides;
    }

    private function compareLayout($sample_pptx, $testpptx){
        $sample_slides = $this->getSlidesArray($sample_pptx);
        $tested_slides = $this->getSlidesArray($testpptx);
        $scored = 0;
        $max = 0;
        for ($i = 0; $i < min(count($sample_slides), count($tested_slides)); $i++){
            $scored += $this->compareSlideLayouts($sample_slides[$i], $tested_slides[$i]);
            $max++;
        }
        return array($scored, $max);

    }

    //Подсчет слайдов. Возврящается количество слайдов
    private function compareSlidesCount($sample_pptx ,$analysPptx){
        return array(min(count($analysPptx->getAllSlides()),count($sample_pptx->getAllSlides())),
            max(count($analysPptx->getAllSlides()),count($sample_pptx->getAllSlides())));
    }

    //Получение текста с презентации
    private function getText($PPTX){
        $slidesArray = $PPTX->getAllSlides();
        $result = '';
        foreach ($slidesArray as $slide){
            $result = $result . $this->getTextFromSlide($slide);
        }
        return $result;
    }

    /*
     * Comparing text from original presentation and tested presentation
     * @params $analysPptx -- Analys presentation
     *
     * @return percentage of comparing presentations text. When text is big it can work incorrectly
     */
    private function compareText($sample_pptx, $analysPptx){
        $sampleText = $this->getText($sample_pptx);
        $testText = $this->getText($analysPptx);
        $diff = levenshtein($sampleText, $testText);
        return 1 - abs($diff/max(strlen($sampleText),strlen($testText)));
    }

    //Получение текста со слайда
    private function getTextFromSlide($slide){
        $res = "";
        $shapes = $slide->getShapeCollection();
        foreach($shapes as $shape){
            if($shape instanceof PhpOffice\PhpPresentation\Shape\RichText){
                $paragraphs = $shape->getParagraphs();
                foreach($paragraphs as $paragraph){
                    $res = $res . $paragraph->getPlainText();
                }
            }
        }
        return $res."\n";
    }

    /*
     * Comparing shapes without RichText shapes
     * @params: $analysPptx -- analys presentation
     *
     * @return mark from 0 to 1 with comparing.
     */
    private function compareShapes($sample_pptx, $analysPptx){
        return array(min($this->countNotRichTextShapes($sample_pptx),
            $this->countNotRichTextShapes($analysPptx)),
            max($this->countNotRichTextShapes($analysPptx),
                $this->countNotRichTextShapes($sample_pptx)));
    }

    /*
     * Compare two Shapes from one slide
     * @params: $sampleSlide -- slide with samples count of shapes
     * $testSlide - slide with testing count of shapes
     *
     * @return -- mark from 0 to 1 with comparing slide
     */
    private function compareNotRichTextShapesFromSlide($sampleSlide, $testSlide){
        $smpl = 0;
        $tst = 0;
        foreach ($sampleSlide->getShapeCollection() as $shape){
            if(!$shape instanceof PhpOffice\PhpPresentation\Shape\RichText){
                $smpl++;
            }
        }
        foreach ($testSlide->getShapeCollection() as $shape){
            if(!$shape instanceof PhpOffice\PhpPresentation\Shape\RichText){
                $tst++;
            }
        }

        return array(min($smpl, $tst), max($smpl, $tst));
    }

    /*
     * Counting Shapes from pptx presentation
     * @params: $pptx -- presentation
     * @return -- numbers of shapes in this presentation
     */
    private function countNotRichTextShapes($pptx){
        $slides = $pptx->getAllSlides();
        $cnt = 0;
        foreach($slides as $slide){
            foreach ($slide->getShapeCollection() as $shape){
                if(!$shape instanceof PhpOffice\PhpPresentation\Shape\RichText){
                    $cnt++;
                }
            }
        }
        return $cnt;
    }

    /*
     * Analyses text from all slides from testPptx with samplePptx
     * @params: $testing -- testing pptx
     * @return -- mark from 0 to 1
     */
    public function testFontsText($sample_pptx, $testing){
        $smplSlides = $this->getCollectionSlides($sample_pptx);
        $tstSlides = $this->getCollectionSlides($testing);
        $max = 0;
        $scored = 0;
        for ($i = 0; $i < min(count($smplSlides),count($tstSlides)); $i++){
            $temp = $this->analysTextFromSlide($smplSlides[$i], $tstSlides[$i]);
            $scored += $temp[0];
            $max += $temp[1];
        }
        return array($scored,$max);
    }

    /*
     * Font analyse with 5 parametrs: Size, Bolt, Italic, Underline, Strikethrough
     * @params $sampleSlide -- slide with perfect task
     * @params $testSlide -- testing slide
     * @return -- mark of analyse from 0 to 1
     */
    private function analysTextFromSlide($sampleSlide, $testSlide){
        $sample_shapes = $sampleSlide->getShapeCollection();
        $test_shapes = $testSlide->getShapeCollection();
        $sample_rtb_elements = array();
        $test_rtb_elements = array();

        $max = 0;
        $scored = 0;

        foreach($sample_shapes as $shape){
            $tmp = $this->getCollectionRichTextElements($shape);
            foreach ($tmp as $elem)
                array_push($sample_rtb_elements, $elem);
        }
        foreach($test_shapes as $shape){
            $tmp = $this->getCollectionRichTextElements($shape);
            foreach ($tmp as $elem)
                array_push($test_rtb_elements, $elem);
        }

        for ($i = 0; $i < min(count($sample_rtb_elements),count($test_rtb_elements));$i++){
            $sampleFont = $sample_rtb_elements[$i]->getFont();
            $testFont = $test_rtb_elements[$i]->getFont();
            if($sampleFont == null&& $testFont == null)
                continue;
            $max+=10;
            if($sampleFont== null){
                $scored+=10;
                continue;
            }
            if($testFont == null){
                continue;
            }
            $scored += 5 - 5*levenshtein($sample_rtb_elements[$i]->getText(),
                    $test_rtb_elements[$i]->getText())/max(strlen($sample_rtb_elements[$i]->getText()),
                    strlen($test_rtb_elements[$i]->getText()));
            if($sampleFont->isItalic() == $testFont->isItalic())
            {
                $scored+=1;
            }
            if($sampleFont->getUnderline() == $testFont->getUnderline())
            {
                $scored+=1;
            }
            if($sampleFont->getSize() == $testFont->getSize()){
                $scored += 1;
            }
            if($sampleFont->isBold() == $testFont->isBold()){
                $scored+=1;
            }
            if($sampleFont->isStrikethrough() == $testFont->isStrikethrough()){
                $scored+=1;
            }
        }
        return array($scored,$max);
    }

    /*
     * Getting all RichTextElements from RichTextShape
     * @param: $shape -- @shape from presentation slide
     * @return -- array of RichTextElements from this shape
     */
    private function getCollectionRichTextElements($shape){
        $arrayElements = array();
        if($shape instanceof PhpOffice\PhpPresentation\Shape\RichText){
            $paragraphs = $shape->getParagraphs();
            foreach($paragraphs as $paragraph){
                foreach ($paragraph->getRichTextElements() as $richTextElement) {
                    array_push($arrayElements, $richTextElement);
                }
            }
        }
        return $arrayElements;
    }

    /*
     * Getting array of pptx slides
     * @param $pptx -- instance of pptx File
     * @return -- array of slides from presentation.
     */
    private function getCollectionSlides($pptx){
        $slides = array();
        foreach ($pptx->getAllSlides() as $slide){
            array_push($slides, $slide);
        }
        return $slides;
    }


    private function compareSlideLayouts($sample_slide, $tested_slide){
        if($sample_slide == null || $tested_slide == null)
            return 0;
        if($sample_slide->getSlideLayout()->getLayoutName() ==
            $tested_slide->getSlideLayout()->getLayoutName())
            return 1;
        return 0;
    }
}