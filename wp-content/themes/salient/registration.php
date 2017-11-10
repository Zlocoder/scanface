<?php
/*template name: Registration */

$form_data = array();
if (!empty($_POST)) {
    $form_data = facecheck_user_registration();
}

get_header();
?>

<div class="container-wrap">
    <div class="container main-content">
        <div class="auth">
            <h1>Зарегистрируйтесь</h1>
            <form action="" method="post">
                <input type="hidden" name="success_page" value="<?= (isset($_SESSION['redirect_result_id']) ? add_query_arg('facecheck_photo_id', $_SESSION['redirect_result_id'], get_permalink(get_page_by_title('Result'))) : get_permalink(get_page_by_title('Upload'))) ?>" />

                <div>
                    <input type="text" name="name" class="name" value="<?=(isset($form_data['name']) ? $form_data['name'] : '')?>" placeholder="Имя Фамилия" />
                    <?php if (isset($form_data['errors']['name'])) { ?><label for="name" class="error"><?=$form_data['errors']['name']?></label><?php } ?>
                </div>

                <div>
                    <input type="text" name="email" class="email" value="<?=(isset($form_data['email']) ? $form_data['email'] : '')?>" placeholder="Электронная почта" />
                    <?php if (isset($form_data['errors']['email'])) { ?><label for="email" class="error"><?=$form_data['errors']['email']?></label><?php } ?>
                </div>

                <div>
                    <input type="password" name="password" value="" placeholder="Придумайте пароль" />
                    <?php if (isset($form_data['errors']['password'])) { ?><label for="password" class="error"><?=$form_data['errors']['password']?></label><?php } ?>
                </div>

                <div>
                    <select name="day">
                        <option value="00">День</option>
                        <?php for ($day = 1; $day <= 31; $day++) { ?>
                            <option value="<?=$day?>" <?php if (isset($form_data['day']) && $form_data['day'] == $day) echo 'selected';?>><?=$day?></option>
                        <?php } ?>
                    </select>
                    <select name="month">
                        <option value="00">Месяц</option>
                        <option value="01" <?php if (isset($form_data['month']) && $form_data['month'] == '01') echo 'selected'; ?>>Январь</option>
                        <option value="02" <?php if (isset($form_data['month']) && $form_data['month'] == '02') echo 'selected'; ?>>Февраль</option>
                        <option value="03" <?php if (isset($form_data['month']) && $form_data['month'] == '03') echo 'selected'; ?>>Март</option>
                        <option value="04" <?php if (isset($form_data['month']) && $form_data['month'] == '04') echo 'selected'; ?>>Апрель</option>
                        <option value="05" <?php if (isset($form_data['month']) && $form_data['month'] == '05') echo 'selected'; ?>>Май</option>
                        <option value="06" <?php if (isset($form_data['month']) && $form_data['month'] == '06') echo 'selected'; ?>>Июнь</option>
                        <option value="07" <?php if (isset($form_data['month']) && $form_data['month'] == '07') echo 'selected'; ?>>Июль</option>
                        <option value="08" <?php if (isset($form_data['month']) && $form_data['month'] == '08') echo 'selected'; ?>>Август</option>
                        <option value="09" <?php if (isset($form_data['month']) && $form_data['month'] == '09') echo 'selected'; ?>>Сентябрь</option>
                        <option value="10" <?php if (isset($form_data['month']) && $form_data['month'] == '10') echo 'selected'; ?>>Октябрь</option>
                        <option value="11" <?php if (isset($form_data['month']) && $form_data['month'] == '11') echo 'selected'; ?>>Ноябрь</option>
                        <option value="12" <?php if (isset($form_data['month']) && $form_data['month'] == '12') echo 'selected'; ?>>Декабрь</option>
                    </select>
                    <select name="year">
                        <option value="0000">Год</option>
                        <?php for ($year = 2000; $year > 1900; $year--) { ?>
                            <option value="<?=$year?>" <?php if (isset($form_data['year']) && $form_data['year'] == $year) echo 'selected';?>><?=$year?></option>
                        <?php } ?>
                    </select>
                </div>

                <input class="nectar-button accent-color submit" type="submit" value="Зарегистрироваться" />
            </form>
            <p><b>или</b></p>
            <p><a href="https://www.facebook.com/dialog/oauth?client_id=759154490783588&scope=email,user_birthday&redirect_uri=<?=urlencode(home_url() . '/wp-admin/admin-post.php?action=facecheck_oauth_facebook')?>" class="nectar-button facebook-auth">facebook</a></p>
            <p><a href="https://www.linkedin.com/uas/oauth2/authorization?response_type=code&client_id=75ffuo7grpudeh&state=DCEEFWF45453sdffef424&scope=r_fullprofile,r_emailaddress&redirect_uri=<?=urlencode(home_url() . '/wp-admin/admin-post.php?action=facecheck_oauth_linkedin')?>" class="nectar-button linkedin-auth">Linked<img src="http://www.scanface.com.ua/wp-content/uploads/2013/09/iconLinked.png" /></a></p>
        </div>
    </div>
</div>

<?php get_footer(); ?>
