<?php
/*template name: Result */
if (isset($_POST['marker'])) {
    Facecheck::saveMarkers(get_query_var('facecheck_photo_id'), $_POST['marker']);
}

$report = facecheck_get_result(get_query_var('facecheck_photo_id'));
$photo = Facecheck::getPhoto(get_query_var('facecheck_photo_id'));
$rows = $wpdb->get_results("SELECT * FROM `{$wpdb->prefix}facecheck_report_sections`", ARRAY_A);
$sections = array();
foreach ($rows as $row) {
    $sections[$row['id']] = $row;
}

$userGroups = isset($_SESSION['facecheck_user']) ? $_SESSION['facecheck_user']->groups() : array();
$photoGroups = Facecheck::getPhotoGroups($photo->id);

if (count($userGroups)) {
    foreach($userGroups as $index => $group) {
        if (isset($photoGroups[$group['id']])) {
            $userGroups[$index]['selected'] = true;
        } else {
            $userGroups[$index]['selected'] = false;
        }
    }
}

$profCats = Facecheck::getProfessionCategories();
$rows = Facecheck::getProfessions(isset($_SESSION['facecheck_user']) ? $_SESSION['facecheck_user']->id : 0);
foreach ($rows as $row) {
    if (!is_array($profCats[$row['id_category']])) {
        $profCats[$row['id_category']] = array(
            'id' => 0,
            'name' => 'Другие профессии',
            'profs' => array()
        );
    }

    if (!is_array($profCats[$row['id_category']]['profs'])) {
        $profCats[$row['id_category']]['profs'] = array();
    }

    $profCats[$row['id_category']]['profs'][$row['id']] = $row;
}

$specifications = Facecheck::getSpecifications();
if ($_SESSION['facecheck_user'] && $_SESSION['facecheck_user']->use_personal_specifications) {
    $prof_spec = $wpdb->get_results("
        SELECT `prof`.`id_profession`, `prof`.`id_specification`, `prof`.`description`, IF(`cust`.`id`, `cust`.`left`, `prof`.`left_value`) AS `left_value`, IF(`cust`.`id`, `cust`.`right`, `prof`.`right_value`) AS `right_value`
        FROM `{$wpdb->prefix}facecheck_professions_specifications` AS `prof`
        LEFT JOIN `{$wpdb->prefix}facecheck_custom_specifications` AS `cust` ON `cust`.`id_profession`=`prof`.`id_profession` AND `cust`.`id_specification`=`prof`.`id_specification`
        WHERE `cust`.`id` IS NULL OR `cust`.`id_user` = {$_SESSION['facecheck_user']->id}
    ", ARRAY_A);
} else {
    $prof_spec = $wpdb->get_results("SELECT * FROM `{$wpdb->prefix}facecheck_professions_specifications`", ARRAY_A);
}
foreach ($prof_spec as $item) {
    if (!is_array($specifications[$item['id_specification']]['profs'])) {
        $specifications[$item['id_specification']]['profs'] = array();
    }

    $specifications[$item['id_specification']]['profs'][$item['id_profession']] = $item;
}

if (isset($_SESSION['facecheck_user'])) {
    $emails = $wpdb->get_results("
        SELECT `email`
        FROM `{$wpdb->prefix}facecheck_users_emails`
        WHERE `id_user` = {$_SESSION['facecheck_user']->id}
        ORDER BY `id`
  ", ARRAY_A);
} else {
    $emails = array();
}

get_header();
?>

<script type="text/javascript">
    var isLogged = <?= isset($_SESSION['facecheck_user']) ? 'true' : 'false' ?>;

    var professions = <?= json_encode($profCats) ?>;
    var specifications = <?= json_encode($specifications) ?>;
    var specsCount = <?= count($specifications) ?>;
</script>

<div class="container-wrap stepContent">
    <div class="navigation">
        <div id="userlinks" class="container">
            <ul class="col span_12">
                <li class="oneQuart active"><a href="<?=get_permalink($wp_query->post->ID)?>">Тестирование</a></li>
                <li class="twoQuart"><a href="<?=get_permalink(get_page_by_title('History'))?>" <?php if(!isset($_SESSION['facecheck_user'])) { ?>onclick="showRegistrationPopup(); return false;"<?php } ?>>История тестирований<?php if (!isset($_SESSION['facecheck_user'])) { ?><span class="hint">Зарегистрируйтесь, чтобы получить больше возможностей</span><?php } ?></a></li>
                <li class="oneQuart"><a href="<?=get_permalink(get_page_by_title('Profile'))?>" <?php if(!isset($_SESSION['facecheck_user'])) { ?>onclick="showRegistrationPopup(); return false;"<?php } ?>>Мой профиль<?php if (!isset($_SESSION['facecheck_user'])) { ?><span class="hint">Зарегистрируйтесь, чтобы получить больше возможностей</span><?php } ?></a></li>
            </ul>
        </div>
    </div>

    <div class="container main-content">
        <div class="row">
            <div id="steps">
                <ul class="col span_12">
                    <li class="first"><h6 class="title">Загрузка фото</h6><div class="number"><a href="<?=get_permalink(get_page_by_title('Upload'))?>">1</a></div><hr/></li>
                    <li class="second"><h6 class="title">Проверка</h6><div class="number"><a href="<?=add_query_arg('facecheck_photo_id', get_query_var('facecheck_photo_id'), get_permalink(get_page_by_title('Markers')))?>">2</a></div><hr class="one"/><hr class="two"/></li>
                    <li class="last active"><h6 class="title">Результаты</h6><div class="number">3</div><hr/></li>
                </ul>
            </div>
        </div>

        <div class="row">
            <div class="reportHeader">
                <h6>Просмотр отчета</h6>
                <ul class="report-buttons" style="float:right;">
                    <li>
                        <a class="save" href="#" onclick="<?php if (!isset($_SESSION['facecheck_user'])) { echo 'showRegistrationPopup(); '; } ?>return false;"></a>
                        <?php if (isset($_SESSION['facecheck_user'])) { ?>
                            <span class="hint">Сохранить</span>
                        <?php } else { ?>
                            <span class="hint">Зарегистрируйтесь, чтобы получить больше возможностей</span>
                        <?php } ?>
                    </li>

                    <li>
                        <a class="print" href="#" onclick="print(); return false;"></a>
                        <span class="hint">Распечатать</span>
                    </li>

                    <li>
                        <a class="letter" href="#" onclick="<?php if (!isset($_SESSION['facecheck_user'])) { echo 'showRegistrationPopup(); '; } ?>return false;"></a>

                        <?php if (isset($_SESSION['facecheck_user'])) { ?>
                                <div class="hint">
                                    <div style="margin-bottom: 5px;">Отправить отчет по адресам</div>

                                    <?php $i = 0; ?>
                                    <?php foreach ($emails as $email) { ?>
                                        <div>
                                            <input type="checkbox" class="email_checkbox" id="email_<?= $i ?>" value="<?= $email['email'] ?>" checked />
                                            <label for="email_<?= $i ?>"><?= $email['email'] ?></label>
                                        </div>
                                        <?php $i++ ?>
                                    <?php } ?>

                                    <div>
                                        <a id="newAddress" href="#" onclick="return false;">Новый адрес</a>
                                    </div>
                                </div>
                        <?php } else { ?>
                            <span class="hint">Зарегистрируйтесь, чтобы получить больше возможностей</span>
                        <?php } ?>
                    </li>
                </ul>
            </div>

            <div class="report">
                <div class="persone">
                    <img class="image" src="<?=content_url('/photos/face_'.get_query_var('facecheck_photo_id').'.jpg')?>" />
                    <div class="info">
                        <form action="/wp-admin/admin-post.php?action=facecheck_save_photo" method="post">
                            <input type="hidden" id="report_id" value="<?=get_query_var('facecheck_photo_id')?>" />

                            <div class="line">
                                <span class="name">Дата тестирования:</span>
                                <span class="date"><?=$photo->date?></span>
                            </div>

                            <div class="line">
                                <span class="name">Имя тестируемого:</span>
                                <input type="text" id="report_name" value="<?=$photo->name?>" <?php if (!isset($_SESSION['facecheck_user'])) { ?> readonly onclick="showRegistrationPopup();" <?php } ?>  />
                            </div>

                            <div class="line">
                                <span class="name">Категория:</span>
                                <div class="checkboxes">
                                    <?php if (count($userGroups)) { ?>
                                        <?php foreach($userGroups as $group) { ?>
                                            <div>
                                                <input type="checkbox" class="group_checkbox" id="group_<?=$group['id']?>" value="<?=$group['id']?>" <?php if($group['selected']) echo 'checked' ?> />
                                                <label for="group_<?=$group['id']?>"><?=$group['name']?></label>
                                            </div>
                                        <?php } ?>
                                    <?php } ?>

                                    <div>
                                        <a id="newCategory" href="#" onclick="<?php if (!isset($_SESSION['facecheck_user'])) { ?>showRegistrationPopup(); <?php } ?>return false;">Новая категория</a>
                                    </div>
                                </div>
                            </div>

                            <div class="line">
                                <span class="name">Личный коментарий:</span>
                                <div class="checkboxes">
                                    <div>
                                        <a href="#" <?php if (!isset($_SESSION['facecheck_user'])) { ?> readonly onclick="showRegistrationPopup();" <?php } else { ?> id="showComment" onclick="return false;" <?php } ?>>Добавить коментарий</a>
                                    </div>
                                    <textarea id="report_comment" style="display: none;"><?=$photo->comment?></textarea>
                                </div>
                            </div>
                        </form>

                        <img src="<?=get_template_directory_uri() . '/img/shtamp.png'?>" width="148" height="148"/>
                    </div>
                </div>

                <div class="profession">
                    <div class="profession__img">
                        <div class="profession__img-val">75<span>%</span></div>
                    </div>
                    <div class="profession__left">
                        <div class="profession__left-prof">
                            <a href="#" id="select-prof" onclick="return false;">Выберите должность</a>
                            <ul id="profession__list" class="profession__list" style="display: none;">
                                <?php foreach ($profCats as $item) { ?>
                                    <li><a href="" data-cat-id="<?= $item['id'] ?>" onclick="return false;"><?= $item['name'] ?></a><span></span></li>
                                <?php } ?>
                            </ul>
                            <ul id="profession__list-inner" class="profession__list-inner" style="display: none;">
                                <?php foreach ($profCats as $item) { ?>
                                    <?php foreach ($item['profs'] as $prof) { ?>
                                        <li data-cat-id="<?= $item['id'] ?>" style="display: none;"><a <?= $prof['id_user'] ? '' : 'class="base"' ?> href="" data-prof-id="<?= $prof['id'] ?>" onclick="return false;"><?= $prof['name'] ?></a></li>
                                    <?php } ?>
                                <?php } ?>
                            </ul>
                        </div>
                    </div>
                    <div class="profession__right"><p class="profession__active-text">Вы узнаете ее соответствие психотипу и профиль кандидата</p></div>
                </div>

                <?php if($report) { ?>
                    <div class="candidate-menu">
                        <div class="candidate-menu__navig">
                            <a href="" class="active" onclick="showLegends(); return false;">Профиль кандидата</a>
                            <a href="" onclick="showSections(); return false;">Описательная характеристика</a>
                        </div>
                    </div>

                    <?php foreach ($report['specifications'] as $index => $specification) { ?>
                        <div class="legend" data-spec-id="<?= $index ?>" data-spec-val="<?= $specification['value'] ?>">
                            <div class="legend__dash"></div>

                            <div class="legend__left">
                                <div class="legend__circle-dash"></div>
                                <div class="legend__head">
                                    <div class="legend__head-title legend__head-title-from">
                                        <?= $specification['left_title'] ?>
                                        <a href="" class="legend__head-title-info" onclick="return false;">
                                            i
                                            <span class="legend-hint"><?= $specification['left_text'] ?></span>
                                        </a>
                                    </div>

                                    <div class="legend__head-title legend__head-title-to">
                                        <?= $specification['right_title'] ?>
                                        <a href="" class="legend__head-title-info" onclick="return false;">
                                            i
                                            <span class="legend-hint"><?= $specification['right_text'] ?></span>
                                        </a>
                                    </div>
                                </div>
                                <div class="legend__diagramm">
                                    <div class="legend__diagramm-progress">
                                        <div class="legend__diagramm-progress-inner" style="position: relative;"></div>
                                        <?php $percent = $specification['value'] * 100 / ($specification['right_value'] - $specification['left-value']) ?>
                                        <div class="legend__diagramm-circle" style="left: <?= $percent ?>%;"></div>
                                    </div>
                                    <ul class="legend__diagramm-grid">
                                        <?php for ($v = 1; $v <= 10; $v++) { ?>
                                            <li></li>
                                        <?php } ?>
                                    </ul>
                                </div>
                            </div>

                            <div class="legend__right">
                                <div class="legend__right-title"><?= $specification['name'] ?></div>
                                <p>Выберите должность</p>
                            </div>

                            <div class="clear"></div>
                        </div>
                    <?php } ?>

                    <?php foreach ($report['sections'] as $index => $section) { ?>
                        <?php if($_SESSION['facecheck_user'] || $index < 4) { ?>
                            <div class="section" style="display: none;">
                                <div class="title"><img src="<?=plugins_url('facecheck/sicons/' . $section['section_id'] . '.jpg')?>" /> <?=$sections[$index]['name']?><a href="#" class="toggle"></a></div>
                                <div class="content"><?=$section['text']?></div>
                            </div>
                        <?php } ?>
                    <?php } ?>

                    <?php if (!isset($_SESSION['facecheck_user'])) { ?>
                        <div class="section" style="display: none;">
                            <div class="title"><?=$sections[4]['name']?><a href="#" class="toggle"></a></div>
                        </div>
                        <div class="button">
                            <a class="nectar-button accent-color" href="<?=get_permalink(get_page_by_title('Регистрация'))?>">Бесплатная регистрация</a>
                        </div>
                    <?php } ?>
                <?php } ?>

                <script type="text/javascript">
                    jQuery(function() {
                        jQuery('a.toggle').click(function(e) {
                            e.preventDefault();
                            jQuery(this).parent().next().toggle();
                        })
                    });
                </script>
            </div>
        </div>
    </div>
</div>

<div style="display: none;">
    <div id="registration-popup" class="popup">
        <div class="content">
            <p>Зарегистрируйтесь, что бы получить<br/>больше возможностей</p>
            <a class="nectar-button accent-color" href="<?=get_permalink(get_page_by_title('Регистрация'))?>">Бесплатная регистрация</a>
        </div>
    </div>

    <div id="report_letter_success-popup" class="popup">
        <div class="content">
            <p>Отчет отправлен</p>
        </div>
    </div>

    <div id="report_letter_fail-popup" class="popup">
        <div class="content">
            <p>Произойшла ошибка при отправлении отчета</p>
        </div>
    </div>

    <div id="report_save_success-popup" class="popup">
        <div class="content">
            <p>Отчет сохранен</p>
        </div>
    </div>

    <div id="report_save_fail-popup" class="popup">
        <div class="content">
            <p>Произойшла ошибка при сохранении отчета</p>
        </div>
    </div>
</div>

<?php get_footer(); ?>