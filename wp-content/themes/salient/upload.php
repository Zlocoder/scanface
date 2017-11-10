<?php
/*template name: Upload */
get_header();
?>

<script type="text/javascript">
    var redirectUrl = '<?=get_permalink(get_page_by_title('Markers'))?>';
</script>

<div class="container-wrap stepContent">
	<div class="navigation">
        <div id="userlinks" class="container">
            <ul class="col span_12">
                <li class="oneQuart active"><a href="<?=get_permalink($wp_query->post->ID)?>">Тестирование</a></li>
                <li class="twoQuart"><a href="<?=get_permalink(get_page_by_title('History'))?>" <?php if (!isset($_SESSION['facecheck_user'])) { ?>onclick="showRegistrationPopup(); return false;"<?php } ?>>Мой кабинет<?php if (!isset($_SESSION['facecheck_user'])) { ?><span class="hint">Зарегистрируйтесь, чтобы получить больше возможностей</span><?php } ?></a></li>
                <li class="oneQuart"><a href="<?=get_permalink(get_page_by_title('Profile'))?>" <?php if (!isset($_SESSION['facecheck_user'])) { ?>onclick="showRegistrationPopup(); return false;"<?php } ?>>Мой профиль<?php if (!isset($_SESSION['facecheck_user'])) { ?><span class="hint">Зарегистрируйтесь, чтобы получить больше возможностей</span><?php } ?></a></li>
            </ul>
        </div>
    </div>

    <div class="container main-content">
        <div class="row">
            <div id="steps">
                <ul class="col span_12">
                    <li class="first active"><h6 class="title">Загрузка фото</h6><div class="number">1</div><hr/></li>
                    <li class="second"><h6 class="title">Проверка</h6><div class="number">2</div><hr class="one"/><hr class="two"/></li>
                    <li class="last"><h6 class="title">Результаты</h6><div class="number">3</div><hr/></li>
                </ul>
            </div>
        </div>

        <div class="row">
            <div class="col span_12">
                <div class="col span_3 example">
                    <h6>Пример фото</h6>
			        <img src="http://www.scanface.com.ua/wp-content/uploads/2013/09/photoBig.png" />
			        <h6>Требования к фотографиям</h6>
                    <ul>
                        <li>Фото должно быть качественным не менее 600px по меньшей стороне.</li>
                        <li>Сделано при достаточной освещенности.</li>
                        <li>Один человек.</li>
                        <li>Смотреть должен прямо в кадр.</li>
                    </ul>
                </div>

                <div class="col span_6 upload" style="text-align: center;">
                    <div id="uploader-wrapper">
                        <input id="file-input" type="file" name="photo" accept="image/*" />
                        <div id="uploader"></div>
                    </div>
                </div>

                <div class="col span_3 incorrect">
                   	<h6>Неподходящие фотографии</h6>
                    <img src="http://www.scanface.com.ua/wp-content/uploads/2013/09/photo1.png" />
                    <img src="http://www.scanface.com.ua/wp-content/uploads/2013/09/photo2.png" />
                    <img src="http://www.scanface.com.ua/wp-content/uploads/2013/09/photo3.png" />
                    <img src="http://www.scanface.com.ua/wp-content/uploads/2013/09/photo4.png" />
                </div>
            </div>
        </div>
    </div>
</div>

<div style="display: none;">
    <div id="upload-popup" class="popup">
        <div class="content">
                <img src="<?=get_template_directory_uri() . '/img/loading.gif'?>" />
                <p>Пожалуйста подождите, фото обрабатывается...</p>
        </div>
    </div>


    <div id="bad-photo" class="popup">
        <div class="content">
            <img src="<?=get_template_directory_uri() . '/img/icons/error1.png'?>" />
            <p>Фотография не пригодна для анализа,<br/><a href="#" onclick="jQuery.magnificPopup.close(); initUploader(); return false;">попробуйте еще раз</a></p>
        </div>
    </div>

    <?php if (!isset($_SESSION['facecheck_user'])) { ?>
        <div id="registration-popup" class="popup">
            <div class="content">
                <p>Зарегистрируйтесь, что бы получить<br/>больше возможностей</p>
                <a class="nectar-button accent-color" href="<?=get_permalink(get_page_by_title('Регистрация'))?>">Бесплатная регистрация</a>
            </div>
        </div>

        <script type="text/javascript">
            jQuery(function() {
                showRegistrationPopup = function() {
                    jQuery.magnificPopup.open({
                        items: {
                            src: jQuery('#registration-popup'),
                            type: 'inline'
                        }
                    })
                }
            })
        </script>
    <?php } ?>
</div>

<?php get_footer(); ?>