<?php
/*template name: Upload */
if (!empty($_POST)) {
    facecheck_save_markers();
}

get_header();
?>

<?php
    $currentPhoto = facecheck_get_photo(get_query_var('facecheck_photo_id'));
?>

<script type="text/javascript">
    var photoId = <?=$currentPhoto->id?>;
    var markers = <?=$currentPhoto->markers?>;
    var editScale = 2;
    var originalWidth = 200;
    var originalHeight = 280;
    var holdDelay = 1500;
</script>

<div class="container-wrap stepContent">
    <div class="navigation">
        <div id="userlinks" class="container">
            <ul class="col span_12">
                <li class="oneQuart active"><a href="<?=get_permalink($wp_query->post->ID)?>">Тестирование</a></li>
                <li class="twoQuart"><a href="<?=get_permalink(get_page_by_title('History'))?>" <?php if (!isset($_SESSION['facecheck_user'])) { ?>onclick="showRegistrationPopup(); return false;"<?php } ?>>История тестирований<?php if (!isset($_SESSION['facecheck_user'])) { ?><span class="hint">Зарегистрируйтесь, чтобы получить больше возможностей</span><?php } ?></a></li>
                <li class="oneQuart"><a href="<?=get_permalink(get_page_by_title('Profile'))?>" <?php if (!isset($_SESSION['facecheck_user'])) { ?>onclick="showRegistrationPopup(); return false;"<?php } ?>>Мой профиль<?php if (!isset($_SESSION['facecheck_user'])) { ?><span class="hint">Зарегистрируйтесь, чтобы получить больше возможностей</span><?php } ?></a></li>
            </ul>
        </div>
    </div>


    <div class="container main-content">
        <div class="row">
            <div id="steps">
                <ul class="col span_12">
                    <li class="first"><h6 class="title">Загрузка фото</h6><div class="number"><a href="<?=get_permalink(get_page_by_title('Upload'))?>">1</a></div><hr/></li>
                    <li class="second active"><h6 class="title">Проверка</h6><div class="number">2</div><hr class="one"/><hr class="two"/></li>
                    <li class="last"><h6 class="title">Результаты</h6><div class="number">3</div><hr/></li>
                </ul>
            </div>
        </div>

        <div class="row">
            <div class="col span_12">
                <div class="col span_3 example">
                    <h6>Расставьте маркеры как на примере</h6>
			        <div class="arrow"></div>
                    <div class="image" style="position: relative">
                        <div class="marker static" title="ml" style="left: 103px; top: 202px;"></div>
                        <div class="marker static" title="mb" style="left: 130px; top: 215px;"></div>
                        <div class="marker static" title="mr" style="left: 156px; top: 199px;"></div>
                        <div class="marker static" title="mt" style="left: 131px; top: 200px;"></div>

                        <div class="marker static" title="ylo" style="left: 83px; top: 141px;"></div>
                        <div class="marker static" title="ylt" style="left: 98px; top: 136px;"></div>
                        <div class="marker static" title="yli" style="left: 114px; top: 144px;"></div>
                        <div class="marker static" title="ylb" style="left: 98px; top: 147px;"></div>

                        <div class="marker static" title="yri" style="left: 144px; top: 144px;"></div>
                        <div class="marker static" title="yrt" style="left: 159px; top: 134px;"></div>
                        <div class="marker static" title="yro" style="left: 174px; top: 140px;"></div>
                        <div class="marker static" title="yrb" style="left: 160px; top: 147px;"></div>

                        <div class="marker static" title="blo" style="left: 74px; top: 127px;"></div>
                        <div class="marker static" title="blm" style="left: 85px; top: 122px;"></div>
                        <div class="marker static" title="bli" style="left: 115px; top: 133px;"></div>

                        <div class="marker static" title="bri" style="left: 143px; top: 132px;"></div>
                        <div class="marker static" title="brm" style="left: 170px; top: 122px;"></div>
                        <div class="marker static" title="bro" style="left: 181px; top: 125px;"></div>

                        <div class="marker blink" title="ml" style="display: none; top: 117px; left: 43px;"></div>
                        <div class="marker blink" title="mb" style="display: none; top: 157px; left: 126px;"></div>
                        <div class="marker blink" title="mr" style="display: none; top: 107px; left: 209px;"></div>
                        <div class="marker blink" title="mt" style="display: none; top: 117px; left: 127px;"></div>

                        <div class="marker blink" title="ylo" style="display: none; top: 121px; left: 82px;"></div>
                        <div class="marker blink" title="ylt" style="display: none; top: 103px; left: 120px;"></div>
                        <div class="marker blink" title="yli" style="display: none; top: 130px; left: 170px;"></div>
                        <div class="marker blink" title="ylb" style="display: none; top: 142px; left: 120px;"></div>

                        <div class="marker blink" title="yri" style="display: none; top: 130px; left: 73px;"></div>
                        <div class="marker blink" title="yrt" style="display: none; top: 103px; left: 120px;"></div>
                        <div class="marker blink" title="yro" style="display: none; top: 117px; left: 165px;"></div>
                        <div class="marker blink" title="yrb" style="display: none; top: 139px; left: 120px;"></div>

                        <div class="marker blink" title="blo" style="display: none; top: 75px; left: 48px;"></div>
                        <div class="marker blink" title="blm" style="display: none; top: 60px; left: 77px;"></div>
                        <div class="marker blink" title="bli" style="display: none; top: 93px; left: 170px;"></div>

                        <div class="marker blink" title="bri" style="display: none; top: 93px; left: 67px;"></div>
                        <div class="marker blink" title="brm" style="display: none; top: 60px; left: 152px;"></div>
                        <div class="marker blink" title="bro" style="display: none; top: 69px; left: 189px;"></div>
                    </div>
                </div>

                <div class="col span_6 markers" style="text-align: center;">
                    <div id="face">
                        <img src="<?=content_url('/photos/face_' . $currentPhoto->id . '.jpg')?>"/>
                        <div id="gag"></div>
                    </div>
                </div>

                <div class="col span_3 showRes" style="text-align: right;">
			        <div class="buttons">
                        <input type="checkbox" id="canSubmit" />
                        <label class="small" for="canSubmit">Маркеры расставлены правильно</label>
                        <div class="clear"></div>
                        <a id="saveMarkers" class="nectar-button extra-color-2" href="<?=add_query_arg('facecheck_photo_id', get_query_var('facecheck_photo_id'), get_permalink(get_page_by_title('Result')))?>" onclick="return false;">Показать результаты тестирования</a>
				        <hr/>
                    	<a class="reset" href="#">Сбросить расположение маркеров</a>
			        </div>
                </div>
            </div>
        </div>
    </div>
</div>

<form id="submitForm" action="" method="post" style="display: none;">
    <input type="hidden" name="redirectUrl" value="<?=add_query_arg('facecheck_photo_id', get_query_var('facecheck_photo_id'), get_permalink(get_page_by_title('Result')))?>" />
    <input type="hidden" name="id" value="<?=get_query_var('facecheck_photo_id')?>" />

    <input type="hidden" name="marker[ml][x]" />
    <input type="hidden" name="marker[ml][y]" />
    <input type="hidden" name="marker[mb][x]" />
    <input type="hidden" name="marker[mb][y]" />
    <input type="hidden" name="marker[mr][x]" />
    <input type="hidden" name="marker[mr][y]" />
    <input type="hidden" name="marker[mt][x]" />
    <input type="hidden" name="marker[mt][y]" />

    <input type="hidden" name="marker[ylo][x]" />
    <input type="hidden" name="marker[ylo][y]" />
    <input type="hidden" name="marker[ylt][x]" />
    <input type="hidden" name="marker[ylt][y]" />
    <input type="hidden" name="marker[yli][x]" />
    <input type="hidden" name="marker[yli][y]" />
    <input type="hidden" name="marker[ylb][x]" />
    <input type="hidden" name="marker[ylb][y]" />

    <input type="hidden" name="marker[yri][x]" />
    <input type="hidden" name="marker[yri][y]" />
    <input type="hidden" name="marker[yrt][x]" />
    <input type="hidden" name="marker[yrt][y]" />
    <input type="hidden" name="marker[yro][x]" />
    <input type="hidden" name="marker[yro][y]" />
    <input type="hidden" name="marker[yrb][x]" />
    <input type="hidden" name="marker[yrb][y]" />

    <input type="hidden" name="marker[blo][x]" />
    <input type="hidden" name="marker[blo][y]" />
    <input type="hidden" name="marker[blm][x]" />
    <input type="hidden" name="marker[blm][y]" />
    <input type="hidden" name="marker[bli][x]" />
    <input type="hidden" name="marker[bli][y]" />

    <input type="hidden" name="marker[bri][x]" />
    <input type="hidden" name="marker[bri][y]" />
    <input type="hidden" name="marker[brm][x]" />
    <input type="hidden" name="marker[brm][y]" />
    <input type="hidden" name="marker[bro][x]" />
    <input type="hidden" name="marker[bro][y]" />
</form>

<div style="display: none;">
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
