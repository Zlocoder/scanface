<?php
/*template name: Professions */
if (!isset($_SESSION['facecheck_user'])) {
    wp_redirect(home_url());
}

$categories = Facecheck::getProfessionCategories();
$categories[0] = array(
    'id' => 0,
    'name' => 'Другие профессии'
);

$professions = Facecheck::getProfessions($_SESSION['facecheck_user']->id);
foreach ($professions as $profession) {
    if (!isset($categories[$profession['id_category']]['professions'])) {
        $categories[$profession['id_category']]['professions'] = array();
    }

    $categories[$profession['id_category']]['professions'][] = $profession;
}

get_header();
?>

    <div class="container-wrap">
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
            <div class="professions">
                <h1>Каталог профессий</h1>

                <?php foreach ($categories as $category) { ?>
                    <div class="category">
                        <div class="title"><?=$category['name']?><a href="#" class="toggle" onclick="return false;"></a></div>
                        <ul class="content">
                            <li>
                                <a class="new" href="<?= add_query_arg(array('category' => $category['id']), get_permalink(get_page_by_title('Пользовательские характеристики'))) ?>">+ Новая профессия</a>
                            </li>

                            <?php foreach($category['professions'] as $profession) { ?>
                                <li>
                                    <a <?= $profession['id_user'] ? '' : 'class="base"' ?> href="<?= add_query_arg(array('profession' => $profession['id']), get_permalink(get_page_by_title('Пользовательские характеристики'))) ?>"><?= $profession['name'] ?></a>
                                    <?php if ($profession['id_user']) { ?>
                                        <a class="delete" href="" data-id="<?= $profession['id'] ?>" onclick="return false;">удалить</a>
                                    <?php } else { ?>
                                        <span class="base_label" href="">базовая профессия</span>
                                    <?php } ?>
                                </li>
                            <?php } ?>
                        </ul>
                    </div>
                <?php } ?>
            </div>
        </div>

        <script type="text/javascript">
            jQuery(function() {
                jQuery('.professions .title .toggle').click(function() {
                    jQuery(this).toggleClass('closed');
                    jQuery(this).parent().next().toggle();
                });

                jQuery('a.delete').click(function() {
                    var $this = jQuery(this);

                    jQuery.ajax({
                        url: '/wp-admin/admin-ajax.php',
                        type: 'post',
                        data: {
                            action: 'facecheck_delete_profession',
                            profession: $this.data('id')
                        },
                        success: function(response) {
                            if (response == 'success') {
                                $this.parent().fadeOut(1000);
                            }
                        }
                    })
                })
            });
        </script>
    </div>

<?php get_footer(); ?>