<?php
include __DIR__ . '/tcpdf/tcpdf.php';

class FacecheckReportPDF extends TCPDF {
    private $report;
    private $photo;

    public function Header() {}

    private function _t($text) {
        //return mb_convert_encoding($text, 'Windows-1251', 'UTF-8');
        return $text;
    }

    private function writePersone() {
        $this->AddPage();
        $this->Image(content_url('/photos/face_' . $this->photo->id . '.jpg'), 20, 20, 160, 224);
        $this->Image(get_template_directory_uri() . '/img/shtamp.jpg', 470, 20, 110, 110);

        $this->SetDrawColor(217, 217, 217);
        $this->Rect(20, 20, 160, 224);

        $this->SetXY(200, 20);
        $this->SetFont('dejavusans', '' , 12);
        $this->SetTextColor(102, 102, 102);
        $this->Write(12, $this->_t('Дата тестирования: '));

        $this->SetFont('dejavusans', 'B' , 12);
        $this->SetTextColor(51, 51, 51);
        $this->Write(12, $this->_t($this->photo->date));

        $this->SetXY(200, 50);
        $this->SetFont('dejavusans', '' , 12);
        $this->SetTextColor(102, 102, 102);
        $this->Write(12, $this->_t('Имя тестируемого: '));

        $this->SetFont('dejavusans', 'B' , 12);
        $this->SetTextColor(51, 51, 51);
        $this->Write(12, $this->_t($this->photo->name));

        $this->SetXY(200, 80);
        $this->SetFont('dejavusans', 'B' , 12);
        $this->SetTextColor(51, 51, 51);
        $this->SetMargins(200, 0, 20);
        $this->Write(12, $this->_t($this->photo->comment));
        $this->SetMargins(0, 0, 0);
    }

    private function writeSpecifications() {
        $this->SetDrawColor(230, 230, 230);
        $this->SetFillColor(230, 230, 230);

        $i = 0;
        foreach ($this->report['specifications'] as $specification) {
            if ($i == 8) {
                $this->AddPage();
            }

            if ($i <= 7) {
                $top = 280 + floor($i / 2) * 125;
            } else {
                $top = 20 + floor(($i - 8) / 2) * 125;
            }

            if ($i % 2) {
                $left = 305;
            } else {
                $this->Line(20, $top, 575, $top);
                $left = 20;
            }


            $this->SetFont('dejavusans', '', 12);
            $this->SetTextColor(0, 0, 0);
            $this->SetXY($left, $top + 10);
            $this->MultiCell(270, 40, $this->_t($specification['name']), 0, 'C', false, 1, '', '', true, 0, false, true, 0, 'M', true);
            $this->Rect($left, $top + 70, 270, 10, 'F');

            $this->SetFont('dejavusans', '', 8);
            $this->SetTextColor(230, 230, 230);
            $dx = 270/10;
            for ($x = 0, $n = 0; $x <= 270; $x += $dx, $n++) {
                $this->Line($left + $x, $top + 80, $left + $x, $top + 90);
                if ($x == 270) {
                    $this->SetXY($left + $x - 9, $top + 95);
                } else {
                    $this->SetXY($left + $x - 5, $top + 95);
                }
                $this->Cell(10, 10, $this->_t($n));
            }

            $x = $specification['value'] / 10;
            $this->Image(get_template_directory_uri() . '/img/legend-circle.png', 270 * $x + $left - 12, $top + 62, 25, 25);

            $i++;
        }
    }

    private function WriteSections() {
        global $wpdb;

        $this->SetMargins(30, 20, 30);
        $this->SetDrawColor(230, 230, 230);
        $this->SetY(300);

        $rows = $wpdb->get_results("SELECT * FROM `{$wpdb->prefix}facecheck_report_sections`", ARRAY_A);
        $sections = array();
        foreach ($rows as $row) {
            $sections[$row['id']] = $row;
        }

        $n = 1;
        foreach ($this->report['sections'] as $section) {
            $y = $this->GetY();

            $this->SetFont('dejavusans', '', 12);
            $this->SetTextColor(0, 0, 0);

            $this->Line(20, $y, 575, $y);
            $this->Image(dirname(plugin_dir_path( __FILE__ )) . '/sicons/' . $section['section_id'] . '_sm.jpg', 30, $y + 5, 30, 30);
            $this->SetXY(70, $y + 12);
            $this->Write(18, $this->_t($sections[$section['section_id']]['name']));
            $this->Line(20, $y + 40, 575, $y + 40);

            $this->SetXY(30, $y + 50);
            $this->SetFont('dejavusans', '', 10);
            $this->SetTextColor(119, 119, 119);
            $this->WriteHTML($this->_t($section['text']));
            $this->SetY($this->GetY() + 25);

            if ($this->GetY() > 700) {
                $this->Addpage();
                $this->SetY(20);
            }
            $n++;
        }
    }

    public function __construct($id) {
        parent::__construct('P', 'pt', 'A4');

        $this->report = facecheck_get_result($id);
        $this->photo = Facecheck::getPhoto($id);

        $this->SetAuthor('Scanface');
        $this->SetTitle('Scanface анализ');
        $this->writePersone();
        $this->writeSpecifications();
        $this->writeSections();
    }
}