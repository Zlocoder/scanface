<?php
/*template name: Login */

if(isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'facecheck_oauth_facebook' : facecheck_oauth_facebook(); break;
        case 'facecheck_oauth_linkedin' : facecheck_oauth_linkedin(); break;
    }
}

$form_data = array();
if (!empty($_POST)) {
    $form_data = facecheck_user_login();
}

get_header();

if (isset($_SESSION['new_password'])) {
    unset($_SESSION['new_password']);
    ?>

    <script type="text/javascript">
        jQuery(function() {
            jQuery.magnificPopup.open({
                items: {
                    src: jQuery('#new-password'),
                    type: 'inline'
                }
            });
        })
    </script>

    <?php
}
?>

<div class="container-wrap">
    <div class="container main-content">
        <div class="auth">
            <h1>Вход</h1>
            <form action="" method="post">
                <input type="hidden" name="success_page" value="<?= (isset($_SESSION['redirect_result_id']) ? add_query_arg('facecheck_photo_id', $_SESSION['redirect_result_id'], get_permalink(get_page_by_title('Result'))) : get_permalink(get_page_by_title('Upload'))) ?>" />

                <div>
                    <input type="text" class="email" name="email" value="<?=(isset($form_data['email']) ? $form_data['email'] : '')?>" placeholder="Электронная почта" />
                    <?php if (isset($form_data['errors']['email'])) { ?><label for="email" class="error"><?=$form_data['errors']['email']?></label><?php } ?>
                </div>

                <div>
                    <input type="password" name="password" value="" placeholder="Пароль" />
                    <?php if (isset($form_data['errors']['password'])) { ?><label for="email" class="error"><?=$form_data['errors']['password']?></label><?php } ?>
                </div>

                <div>
                    <input type="checkbox" name="remember" id=remember checked />
                    <label for="remember">Запомнить меня</label>
                    <a href="<?= get_permalink(get_page_by_title('New password')) ?>" class="remember">Забыли пароль?</a>
                </div>

                <input class="nectar-button accent-color submit" type="submit" value="Войти" />
            </form>
            <p><b>или</b></p>
            <p><a href="https://www.facebook.com/dialog/oauth?client_id=759154490783588&scope=email,user_birthday&redirect_uri=<?=urlencode(get_permalink(get_page_by_title('Вход')) . '?action=facecheck_oauth_facebook')?>" class="nectar-button facebook-auth">facebook</a></p>
            <p><a href="https://www.linkedin.com/uas/oauth2/authorization?response_type=code&client_id=75ffuo7grpudeh&state=DCEEFWF45453sdffef424&scope=r_fullprofile,r_emailaddress&redirect_uri=<?=urlencode(get_permalink(get_page_by_title('Вход')) . '?action=facecheck_oauth_linkedin')?>" class="nectar-button linkedin-auth">Linked<img src="http://www.scanface.com.ua/wp-content/uploads/2013/09/iconLinked.png" /></a></p>
        </div>
    </div>
</div>

<div style="display: none;">
    <div id="new-password" class="popup">
        <div class="content">
            <p>На ваш email отправлен новый пароль для входа.</p>
        </div>
    </div>
</div>

<?php get_footer(); ?>