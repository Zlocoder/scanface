<?php
/*template name: Profile */

if (!empty($_POST)) {
    $errors = facecheck_save_profile();
}

get_header();
if (!isset($_SESSION['facecheck_user'])) {
    wp_redirect(home_url());
}

?>

<div class="container-wrap">
    <div class="navigation">
        <div id="userlinks" class="container">
            <ul class="col span_12">
                <li class="oneQuart"><a href="<?=get_permalink(get_page_by_title('Upload'))?>">Тестирование</a></li>
                <li class="twoQuart"><a href="<?=get_permalink(get_page_by_title('History'))?>">Мой кабинет</a></li>
                <li class="oneQuart active"><a href="<?=get_permalink(get_page_by_title('Profile'))?>">Мой профиль</a></li>
            </ul>
        </div>
    </div>

    <div class="container main-content">
        <div class="auth">
            <h1>Изменить профиль</h1>
            <form action="" method="post">
                <div>
                    <input type="text" value="<?=$_SESSION['facecheck_user']->email?>" readonly />
                </div>

                <div>
                    <input type="text" name="name" value="<?=$_SESSION['facecheck_user']->name?>" />
                    <?php if (isset($errors['name'])) { ?><label for="email" class="error"><?=$errors['name']?></label><?php } ?>
                </div>

                <div>
                    <?php $uday = substr($_SESSION['facecheck_user']->date_birth, 8, 2); ?>
                    <select name="day">
                        <option value="00">День</option>
                        <?php for ($day = 1; $day <= 31; $day++) { ?>
                            <option value="<?=$day?>" <?php if ($day == $uday) echo 'selected';?>><?=$day?></option>
                        <?php } ?>
                    </select>
                    <?php $umonth = substr($_SESSION['facecheck_user']->date_birth, 5, 2); ?>
                    <select name="month">
                        <option value="00">Месяц</option>
                        <option value="01" <?php if ($umonth == 1) echo 'selected'; ?>>Январь</option>
                        <option value="02" <?php if ($umonth == 2) echo 'selected'; ?>>Февраль</option>
                        <option value="03" <?php if ($umonth == 3) echo 'selected'; ?>>Март</option>
                        <option value="04" <?php if ($umonth == 4) echo 'selected'; ?>>Апрель</option>
                        <option value="05" <?php if ($umonth == 5) echo 'selected'; ?>>Май</option>
                        <option value="06" <?php if ($umonth == 6) echo 'selected'; ?>>Июнь</option>
                        <option value="07" <?php if ($umonth == 7) echo 'selected'; ?>>Июль</option>
                        <option value="08" <?php if ($umonth == 8) echo 'selected'; ?>>Август</option>
                        <option value="09" <?php if ($umonth == 9) echo 'selected'; ?>>Сентябрь</option>
                        <option value="10" <?php if ($umonth == 10) echo 'selected'; ?>>Октябрь</option>
                        <option value="11" <?php if ($umonth == 11) echo 'selected'; ?>>Ноябрь</option>
                        <option value="12" <?php if ($umonth == 12) echo 'selected'; ?>>Декабрь</option>
                    </select>
                    <?php $uyear = substr($_SESSION['facecheck_user']->date_birth, 0, 4); ?>
                    <select name="year">
                        <option value="0000">Год</option>
                        <?php for ($year = 2000; $year > 1900; $year--) { ?>
                            <option value="<?=$year?>" <?php if ($year == $uyear) echo 'selected';?>><?=$year?></option>
                        <?php } ?>
                    </select>
                </div>

                <div>
                    <input type="password" name="old_password" value="" placeholder="Старый пароль" />
                    <?php if (isset($errors['old_password'])) { ?><label for="email" class="error"><?=$errors['old_password']?></label><?php } ?>
                </div>

                <div>
                    <input type="password" name="new_password" value="" placeholder="Новый пароль" />
                    <?php if (isset($errors['new_password'])) { ?><label for="email" class="error"><?=$errors['new_password']?></label><?php } ?>
                </div>

                <input type="submit" class="nectar-button accent-color submit" name="submit" value="Сохранить" />
            </form>
        </div>
    </div>
</div>

<?php get_footer(); ?>