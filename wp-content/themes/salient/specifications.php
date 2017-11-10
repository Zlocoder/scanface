<?php
/* template name: Specifications */
if (!isset($_SESSION['facecheck_user'])) {
    wp_redirect(home_url());
}

if (!isset($_GET['profession']) && !isset($_GET['category'])) {
    wp_redirect(home_url());
}

if (!empty($_POST)) {
    $result = facecheck_save_custom_specifications();
    $errors = $result['errors'];
    $specval = $result['specval'];
}

if (isset($_GET['profession'])) {
    $profession = $wpdb->get_row("SELECT * FROM `{$wpdb->prefix}facecheck_professions` WHERE `id` = {$_GET['profession']}", ARRAY_A);
    if ($profession['id_user'] && $profession['id_user'] != $_SESSION['facecheck_user']->id) {
        wp_redirect(home_url());
    }
}
$specifications = Facecheck::getSpecifications($profession ? $profession['id'] : 0);

if (isset($specval)) {
    foreach ($specval as $id => $val) {
        $specifications[$id]['prof_left'] = $val['left'];
        $specifications[$id]['prof_right'] = $val['right'];
    }
} else if (!$profession) {
    foreach ($specifications as $id => $spec) {
        $specifications[$id]['prof_left'] = 0;
        $specifications[$id]['prof_right'] = 3;
    }
}


get_header();

?>

<div class="container-wrap stepContent">
    <div class="container main-content">
        <div class="row">
            <?php if ($profession) { ?>
                <h1 style="text-align: center; margin-top: 20px;">Редактирование профессии</h1>
            <?php } else { ?>
                <h1 style="text-align: center; margin-top: 20px;">Новая профессия</h1>
            <?php } ?>

            <div class="report edit-specs">
                <form action="" method="post">
                <input type="hidden" name="id_origin" value="<?= $_GET['profession'] ?>" />
                <input id="prof-id" type="hidden" name="id_profession" value="<?= $profession['id_user'] ? $profession['id'] : 0 ?>" />
                <input id="cat-id" type="hidden" name="id_category" value="<?= $profession ? $profession['id_category'] : $_GET['category'] ?>" />

                    <div style="padding: 25px; text-align: center;">
                        <label style="font-family: 'HelveticaNeueCyr-Bold', 'Open Sans', sans-serif; font-size: 18px; line-height: 20px; font-weight: bold; color: #000; margin-right: 20px;">Название</label>
                        <input type="text" name="name" value="<?= $profession['name'] ?>" style="line-height: 16px; padding: 10px; width: 40%;"/>
                        <?php if (isset($errors['name'])) { ?>
                            <label class="error"><?= $errors['name'] ?></label>
                        <?php } ?>
                    </div>

                    <?php foreach($specifications as $id => $specification) { ?>
                        <div class="legend" data-spec-id="<?= $id ?>">
                            <div class="legend__dash"></div>

                            <div class="legend__title"><?= $specification['name'] ?></div>

                            <div class="legend__left">
                                <div class="legend__circle-dash"></div>

                                <div class="legend__head">
                                    <div class="legend__head-title legend__head-title-from">
                                        <?= $specification['left_title'] ?>
                                        <a href="" class="legend__head-title-info">
                                            i
                                            <span class="legend-hint"><?= $specification['left_text'] ?></span>
                                        </a>
                                    </div>

                                    <div class="legend__head-title legend__head-title-to">
                                        <?= $specification['right_title'] ?>
                                        <a href="" class="legend__head-title-info">
                                            i
                                            <span class="legend-hint"><?= $specification['right_text'] ?></span>
                                        </a>
                                    </div>
                                </div>

                                <div class="legend__diagramm">
                                    <div class="legend__diagramm-progress">
                                        <?php
                                            if ($specification['prof_left'] < 0) {
                                                $left = 0;
                                            } else {
                                                $left = $specification['prof_left'];
                                            }

                                            if ($specification['prof_right'] > 10) {
                                                $width = (10 - $left);
                                            } else {
                                                $width = $specification['prof_right'] - $left;
                                            }
                                        ?>
                                        <div class="legend__diagramm-progress-inner" style="position: relative; left: <?= 53.8 * $left ?>px; width: <?= 10 * $width ?>%; "></div>
                                    </div>
                                    <ul class="legend__diagramm-grid">
                                        <?php for ($v = 1; $v <= 10; $v++) { ?>
                                            <li></li>
                                        <?php } ?>
                                    </ul>
                                </div>

                                <input type="hidden" class="spec-value spec-value-left" name="specval[<?= $id ?>][left]" value="<?= $specification['prof_left'] ?>" />
                                <input type="hidden" class="spec-value spec-value-right" name="specval[<?= $id ?>][right]" value="<?= $specification['prof_right'] ?>" />
                            </div>

                            <?php /*
                            <div class="legend__right">
                                <div class="legend__right-title"><?= $specification['name'] ?></div>
                                <div>
                                    <input type="text" class="spec-value spec-value-left" name="specval[<?= $id ?>][left]" value="<?= $specification['prof_left'] ?>" disabled />
                                    <input type="text" class="spec-value spec-value-right" name="specval[<?= $id ?>][right]" value="<?= $specification['prof_right'] ?>" disabled />
                                </div>
                                <?php if ($errors["specval_{$id}"]) { ?>
                                    <label class="error"><?= $errors["specval_{$id}"]; ?></label>
                                <?php } ?>
                            </div>
                            */ ?>


                            <div class="clear"></div>
                        </div>
                    <?php } ?>

                    <div class="clear"></div>

                    <div style="text-align: center; margin-top: 40px; padding-bottom: 20px;">
                        <input type="submit" class="nectar-button accent-color" value="Сохранить" />
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php get_footer() ?>

<script>
    jQuery(function() {
        jQuery('.legend__diagramm-progress-inner').draggable({
            axis: 'x',
            containment: 'parent',
            grid: [53.7, 0],
            drag: function(event, ui) {
                var left_value = Math.round(ui.position.left / 53.8);
                var right_value = left_value + Math.round(jQuery(this).width() / 53.8);
                jQuery(this).parents('.legend').find('.spec-value-left').val(left_value);
                jQuery(this).parents('.legend').find('.spec-value-right').val(right_value);
            }
        })
    })
</script>


























