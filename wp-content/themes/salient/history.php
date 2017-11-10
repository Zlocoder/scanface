<?php
/*template name: History */
if (!isset($_SESSION['facecheck_user'])) {
    wp_redirect(home_url());
}

$name = isset($_GET['filter']) ? $_GET['filter'] : '';
$category = isset($_GET['category']) ? $_GET['category'] : null;
$reverse = isset($_GET['reverse']) ? true : false;

$page = isset($_GET['page_num']) ? $_GET['page_num'] : '1';
if ($category) {
    $pagesCount = $wpdb->get_var("
        SELECT CEILING(COUNT(`p`.`id`) / 4)
        FROM `{$wpdb->prefix}facecheck_photos` AS `p`
        LEFT JOIN `{$wpdb->prefix}facecheck_photo_category` AS `pc` ON `pc`.`photo_id`=`p`.`id`
        WHERE `p`.`user_id`='{$_SESSION['facecheck_user']->id}' AND `pc`.`category_id` IN ({$category}) AND `p`.`date`!='0000-00-00 00:00:00' AND `p`.`name` LIKE '%{$name}%'
    ");
} else {
    $pagesCount = $wpdb->get_var("SELECT CEILING(COUNT(`id`) / 4) FROM `{$wpdb->prefix}facecheck_photos` WHERE `user_id`='{$_SESSION['facecheck_user']->id}' AND `date`!='0000-00-00 00:00:00' AND `name` LIKE '%{$name}%'");
}
$limit = $page * 4 - 4;

if ($category) {
    $photos = $wpdb->get_results("
        SELECT `p`.*
        FROM `{$wpdb->prefix}facecheck_photos` AS `p`
        LEFT JOIN `{$wpdb->prefix}facecheck_photo_category` AS `pc` ON `pc`.`photo_id`=`p`.`id`
        WHERE `p`.`user_id`='{$_SESSION['facecheck_user']->id}' AND `pc`.`category_id` IN ({$category}) AND `p`.`date`!='0000-00-00 00:00:00' AND `p`.`name` LIKE '%{$name}%'
        GROUP BY `p`.`id`
        ORDER BY `p`.`date` " . ($reverse ? 'ASC' : 'DESC') . " LIMIT $limit, 4
    ", ARRAY_A);
} else {
    $photos = $wpdb->get_results("SELECT * FROM `{$wpdb->prefix}facecheck_photos` WHERE `user_id`='{$_SESSION['facecheck_user']->id}' AND `date`!='0000-00-00 00:00:00' AND `name` LIKE '%{$name}%' ORDER BY `date` " . ($reverse ? 'ASC' : 'DESC') . " LIMIT $limit, 4", ARRAY_A);
}

$userGroups = $_SESSION['facecheck_user']->groups();

if ($photos) {
    $photo_id = get_query_var('facecheck_photo_id');
    if ($photo_id) {
        foreach ($photos as $ph) {
            if ($ph['id'] == $photo_id) {
                $photo = Facecheck::getPhoto($photo_id);
                break;
            }
        }
    }

    if (!isset($photo)) {
        $photo = Facecheck::getPhoto($photos[0]['id']);
    }

    if ($page == 1) {
        $afterDeletePage = 1;
    } elseif (count($photos) == 1) {
        $afterDeletePage = $page - 1;
    } else {
        $afterDeletePage = $page;
    }

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

    $rows = $wpdb->get_results("SELECT * FROM `{$wpdb->prefix}facecheck_report_sections`", ARRAY_A);
    $sections = array();
    foreach ($rows as $row) {
        $sections[$row['id']] = $row;
    }
    $report = facecheck_get_result($photo->id);
}

$queryArgs = array();
if (isset($photo_id) && isset($photo) && $photo_id == $photo->id) {
    $queryArgs['facecheck_photo_id'] = $photo_id;
}

if (isset($page) && $page > 1) {
    $queryArgs['page_num'] = $page;
}

if (isset($name) && $name != '') {
    $queryArgs['filter'] = $name;
}

if ($reverse) {
    $queryArgs['reverse'] = '';
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
$prof_spec = $wpdb->get_results("
    SELECT
        `prof`.`id_profession`,
        `prof`.`id_specification`,
        `prof`.`description`,
        `prof`.`left_value`,
        `prof`.`right_value`
    FROM `{$wpdb->prefix}facecheck_professions_specifications` AS `prof`
", ARRAY_A);

foreach ($prof_spec as $item) {
    if (!is_array($specifications[$item['id_specification']]['profs'])) {
        $specifications[$item['id_specification']]['profs'] = array();
    }

    $specifications[$item['id_specification']]['profs'][$item['id_profession']] = $item;
}

$tmp_page = $page;
get_header();
$page = $tmp_page;
unset($tmp_page);

$emails = $wpdb->get_results("
    SELECT `email`
    FROM `{$wpdb->prefix}facecheck_users_emails`
    WHERE `id_user` = {$_SESSION['facecheck_user']->id}
    ORDER BY `id`
", ARRAY_A);

?>

<script type="text/javascript">
    var isLogged = true;
    var page_url = '<?= get_permalink(get_page_by_title('History')); ?>';

    var professions = <?= json_encode($profCats) ?>;
    var specifications = <?= json_encode($specifications) ?>;
    var specsCount = <?= count($specifications) ?>;
</script>

<div class="container-wrap stepContent">
    <div class="navigation">
        <div id="userlinks" class="container">
            <ul class="col span_12">
                <li class="oneQuart"><a href="<?=get_permalink(get_page_by_title('Upload'))?>">Тестирование</a></li>
                <li class="twoQuart active"><a href="<?=get_permalink(get_page_by_title('History'))?>">Мой кабинет</a></li>
                <li class="oneQuart"><a href="<?=get_permalink(get_page_by_title('Profile'))?>">Мой профиль</a></li>
            </ul>
        </div>
    </div>

    <div class="container main-content">
        <div class="row">
            <div class="biglinks col span_12">
                <div class="col span_6">
                    <a href="<?= get_permalink(get_page_by_title('Professions')) ?>">Каталог профессий</a>
                </div>
                <div class="col span_6">
                    <a href="">Онлайн консультант</a>
                </div>
            </div>

            <div class="filter col span_12">
                <input type="text" id="filterName" value="<?=$name?>" placeholder="Поиск по имени" />

                <?php $selectedCats = explode(',', $category); ?>
                <select id="filterCategory" multiple="multiple">
                    <?php foreach($userGroups as $group) { ?>
                        <option value="<?=$group['id']?>" <?php if (in_array($group['id'], $selectedCats)) echo 'selected' ?>><?=$group['name']?></option>
                    <?php } ?>
                </select>

                <select id="filterOrder">
                    <option value="">Вниз</option>
                    <option value="1" <?php if($reverse) echo 'selected'; ?>>Вверх</option>
                </select>
            </div>
        </div>


        <?php if (count($photos)) { ?>
            <div class="row">
                <div class="headHistory">
                    <h6>История</h6>
                    <?php if ($pagesCount > 1) { ?>
                        <div id="pages">
                            <span><?=$page?> из <?=$pagesCount?></span>

                            <?php $args = $queryArgs; unset($args['facecheck_photo_id']); unset($args['page_num']); ?>
                            <?php if($page == '1') { ?>
                                <a class="prev" href="#" onclick="return false;"></a>
                            <?php } elseif ($page == 2) { ?>
                                <a class="prev" href="<?=add_query_arg($args, get_permalink(get_page_by_title('History')))?>"></a>
                            <?php } else { ?>
                                <?php $args['page_num'] = $page - 1; ?>
                                <a class="prev" href="<?=add_query_arg($args, get_permalink(get_page_by_title('History')))?>"></a>
                            <?php } ?>

                            <?php $args = $queryArgs; unset($args['facecheck_photo_id']); unset($args['page_num']); ?>
                            <?php if ($page == $pagesCount) { ?>
                                <a class="next" href="#" onclick="return false;"></a>
                            <?php } else { ?>
                                <?php $args['page_num'] = $page + 1; ?>
                                <a class="next" href="<?=add_query_arg($args, get_permalink(get_page_by_title('History')))?>"></a>
                            <?php } ?>
                        </div>
                    <?php } ?>
                </div>

                <div class="pagePhotos">
                    <?php $args = $queryArgs; ?>
                    <?php foreach($photos as $index => $ph) { ?>
                        <?php $args['facecheck_photo_id'] = $ph['id']; ?>
                        <a class="item <?php if ($ph['id'] == $photo->id) echo 'active';?> <?php if ($index == 3) echo 'last';?>" href="<?=add_query_arg($args, get_permalink(get_page_by_title('History')))?>">
                            <img src="<?=content_url('/photos/face_'. $ph['id'] . '.jpg')?>" />
                            <div class="descr">
                                <span class="name"><?=$ph['name']?></span>
                                <span class="date"><?=$ph['date']?></span>
                            </div>
                        </a>
                    <?php } ?>
                </div>
            </div>

            <div class="row">
                <div class="reportHeader">
                    <h6>Просмотр отчета</h6>
                    <ul class="report-buttons" style="float:right;">
                        <li>
                            <?php $args = $queryArgs; unset($args['facecheck_photo_id']); ?>
                            <?php if ($afterDeletePage == 1) unset($args['page_num']); else $args['page_num'] = $afterDeletePage; ?>
                            <a class="delete" href="/wp-admin/admin-ajax.php?action=facecheck_delete_photo&id=<?=$photo->id?>&redirect=<?=add_query_arg($args, get_permalink(get_page_by_title('History')))?>"></a>
                            <span class="hint">Удалить отчет</span>
                        </li>

                        <li>
                            <a class="edit" href="#" onclick="return false;"></a>
                            <span class="hint">Кликните чтобы перейти в режим редактирования отчета</span>
                        </li>

                        <li style="display: none;">
                            <a class="save" href="#" onclick="return false;"></a>
                            <span class="hint">Сохранить</span>
                        </li>

                        <li>
                            <a class="print" href="#" onclick="print(); return false;"></a>
                            <span class="hint">Распечатать</span>
                        </li>

                        <li>
                            <a class="letter" href="#" onclick="return false;"></a>

                            <div class="hint">
                                <div style="margin-bottom: 5px;">Отправить отчет по адресам</div>

                                <?php $i = 0; ?>
                                <?php foreach($emails as $email) { ?>
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
                        </li>
                    </ul>
                </div>

                <div class="report">
                    <div class="persone">
                        <img class="image" src="<?=content_url('/photos/face_'.$photo->id.'.jpg')?>" />
                        <div class="info">
                            <form action="" method="post">
                                <input type="hidden" id="report_id" value="<?=$photo->id?>" />

                                <div class="line">
                                    <span class="name">Дата тестирования:</span>
                                    <span class="date"><?=$photo->date?></span>
                                </div>

                                <div class="line">
                                    <span class="name">Имя тестируемого:</span>
                                    <input type="text" id="report_name" value="<?=$photo->name?>" readonly />
                                </div>

                                <div class="line">
                                    <span class="name">Категория:</span>
                                    <div class="checkboxes">
                                        <?php if (count($userGroups)) { ?>
                                            <?php foreach($userGroups as $group) { ?>
                                                <div>
                                                    <input type="checkbox" class="group_checkbox" id="group_<?=$group['id']?>" value="<?=$group['id']?>" <?php if($group['selected']) echo 'checked' ?> disabled/>
                                                    <label for="group_<?=$group['id']?>"><?=$group['name']?></label>
                                                </div>
                                            <?php } ?>
                                        <?php } ?>

                                        <div>
                                            <a id="newCategory" href="#" onclick="return false;" style="display: none;">Новая категория</a>
                                        </div>
                                    </div>
                                </div>

                                <div class="line">
                                    <span class="name">Личный коментарий:</span>
                                    <div class="checkboxes">
                                        <div>
                                            <a id="showComment" href="#" onclick="return false;">Добавить коментарий</a>
                                        </div>
                                        <textarea id="report_comment" readonly style="display: none;"><?=$photo->comment?></textarea>
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
                                                <li data-cat-id="<?= $item['id'] ?>"><a <?= $prof['id_user'] ? '' : 'class="base"' ?> href="" data-prof-id="<?= $prof['id'] ?>" onclick="return false;"><?= $prof['name'] ?></a></li>
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
                            <div class="section" style="display: none;">
                                <div class="title"><img src="<?=plugins_url('facecheck/sicons/' . $section['section_id'] . '.jpg')?>"/> <?=$sections[$index]['name']?><a href="#" class="toggle" onclick="return false;"></a></div>
                                <div class="content"><?=$section['text']?></div>
                            </div>
                        <?php } ?>
                    <?php } ?>
                </div>
            </div>
        <? } else { ?>
            <div class="row">
                <p>Извините, отчетов не найдено.</p>
            </div>
        <? } ?>
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