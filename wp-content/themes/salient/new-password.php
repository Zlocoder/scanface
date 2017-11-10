<?php
/*template name: New Password */

$form_data = array();
if (!empty($_POST)) {
    $form_data = facecheck_user_password();
}

get_header();
?>

    <div class="container-wrap">
        <div class="container main-content">
            <div class="auth">
                <h1>Восстановление пароля</h1>
                <form action="" method="post">
                    <input type="hidden" name="success_page" value="<?= get_permalink(get_page_by_title('Вход')) ?>" />

                    <?php $form_data = $_SESSION['facecheck_user_password']; ?>

                    <div>
                        <input type="text" class="email" name="email" value="<?=(isset($form_data['email']) ? $form_data['email'] : '')?>" placeholder="Электронная почта" />
                        <?php if (isset($form_data['errors']['email'])) { ?><label for="email" class="error"><?=$form_data['errors']['email']?></label><?php } ?>
                    </div>

                    <input class="nectar-button accent-color submit" type="submit" value="Восстановить" />
                </form>
            </div>
        </div>
    </div>

<?php get_footer(); ?>