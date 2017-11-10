<?php

if (isset($_GET['ajax'])) {
    echo Facecheck::getReport($_GET['report']); exit;
}

$faceparts = Facecheck::getFaceparts();
$sections = Facecheck::getSections();
$specifications = Facecheck::getSpecifications();

foreach (Facecheck::getFacepartTypes() as $facepart_type) {
    if (empty($faceparts[$facepart_type['id_facepart']]['types'])) {
        $faceparts[$facepart_type['id_facepart']]['types'] = array();
    }

    $faceparts[$facepart_type['id_facepart']]['types'][] = $facepart_type;
}

if (isset($_POST['save_report'])) {
    Facecheck::saveReport($_POST['facepart'], $_POST['text'], $_POST['specification']);
    $faceimg = implode('_', array_values($_POST['facepart'])) . '.png';
    $report = Facecheck::getReport($_POST['facepart']);
} elseif (isset($_POST['get_report'])) {
    $report = Facecheck::getReport($_POST['facepart']);
    $faceimg = implode('_', array_values($_POST['facepart'])) . '.png';
} else {
    $report = Facecheck::getReport(array('1' => '1', '2' => '5', '3' => '9'));
    $faceimg = '1_5_9.png';
}

$editor_settings = array(
    'media_buttons' => false,
    'textarea_rows' => 14,
    'textarea_name' => ''
);

?>

<div class="wrap">
    <h2>Отчеты</h2>
    <form id="facecheck-report" method="post">
        <input type="hidden" id="get_report" name="get_report" value="" />
        <div id="facecheck-report-faceparts">
            <?php foreach ($faceparts as $facepart) : ?>
                <div class="facepart">
                    <label for="facepart_<?=$facepart['id']?>"><?=$facepart['name']?></label>
                    <select name="facepart[<?=$facepart['id']?>]" id="facepart_<?=$facepart['id']?>">
                        <?php foreach($facepart['types'] as $type) : ?>
                            <option value="<?=$type['id']?>" <?php if (isset($_POST['facepart']) && $_POST['facepart'][$facepart['id']] == $type['id']) echo 'selected'; ?>><?=$type['name']?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endforeach; ?>
            <div id="face-img">
                <img  src="<?=plugins_url('facecheck/faceimages/') . $faceimg;?>" />
            </div>
            <div>
                <input type="submit" class="button button-primary button-large" name="save_report" id="save_report" value="Сохранить" />
            </div>
        </div>

        <div id="facecheck-report-content">
            <div class="tab">
                <input type="radio" id="report-content-1" name="facecheck-report-content-radio" checked />
                <label for="report-content-1">Характеристики</label>

                <div class="content">
                    <?php foreach($report['specifications'] as $specification) : ?>
                        <div class="spec">
                            <span class="name"><?= $specification['name'] ?></span>
                            <input type="text" name="specification[<?= $specification['id'] ?>]" value="<?= $specification['value'] ?>" />
                            <span class="right-value"><?= $specification['right_value']?></span>
                            <div class="spec-line" data-left-value="<?= $specification['left_value']?>" data-right-value="<?= $specification['right_value']?>">
                                <?php
                                    if ($specification['value']) {
                                        $leftValue  = $specification['left_value'];
                                        $rightValue = $specification['right_value'];
                                        $percent = $specification['value'] * 100 / ($rightValue - $leftValue);
                                        $newPos = $percent * 1.5 - 2;
                                    } else {
                                        $newPos = 0;
                                    }
                                ?>

                                <div class="spec-pos" style="left: <?= $newPos ?>px;"></div>
                            </div>
                            <span class="left-value"><?= $specification['left_value']?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="tab">
                <input type="radio" id="report-content-2" name="facecheck-report-content-radio"/>
                <label for="report-content-2">Cекции</label>

                <div class="content">
                    <div id="facecheck-report-section-name">
                        <?php foreach($sections as $section) : ?>
                            <div><?=$section['name']?></div>
                        <?php endforeach; ?>
                    </div>
                    <div id="facecheck-report-section">
                        <?php foreach($sections as $section) : ?>
                            <?php $editor_settings['textarea_name'] = 'text[' . $section['id'] . ']'; ?>
                            <?php wp_editor(isset($_POST['save_report']) ? wp_kses_stripslashes($_POST['text'][$section['id']]) : (empty($report) ? '' : $report['sections'][$section['id']]['text']), 'text_'. $section['id'], $editor_settings)?>
                        <?php endforeach; ?>
                        <div class="clear"></div>
                    </div>
                </div>
            </div>
        </div>
    </form>


    <script type="text/javascript">
        $(function() {
            $('#facecheck-report-section-name :first').addClass('active');
            //$('#facecheck-report-section .wp-editor-wrap').not(':first').hide();

            $('#facecheck-report-section-name div').click(function() {
                if (!$(this).hasClass('active')) {
                    $('#facecheck-report-section-name .active').removeClass('active');
                    $('#facecheck-report-section .wp-editor-wrap').hide();
                    $(this).addClass('active');
                    $('#facecheck-report-section .wp-editor-wrap').eq($('#facecheck-report-section-name div').index($(this))).show();
                }
            });
            $('.facepart select').change(function() {
                $('#get_report').val('1');
                $('#facecheck-report').submit();
            });

            setTimeout(hideEditors, 500);

            $('#facecheck-report-content .content .spec input').change(function() {
                var $line = $(this).siblings('.spec-line');
                var $pos = $line.children();
                var leftValue  = $line.data('left-value');
                var rightValue = $line.data('right-value');

                var percent = $(this).val() * 100 / (rightValue - leftValue);
                var newPos = percent * $line.width() / 100 - 2;

                $pos.css('left', newPos + 'px');
            });
        });

        function hideEditors() {
            $('#facecheck-report-section .wp-editor-wrap').hide();
            $('#facecheck-report-section .wp-editor-wrap:first').show();
        }
    </script>
</div>
