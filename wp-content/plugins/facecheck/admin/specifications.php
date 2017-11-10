<div class="wrap">
    <h2>
        Характеристики
        <a href="/wp-admin/admin.php?page=facecheck/admin/specifications.php&new" class="add-new-h2">Добавить новую</a>
    </h2>

    <?php
    if (isset($_GET['specification']) || isset($_GET['new'])) {
        if (isset($_GET['specification'])) {
            $specification = $wpdb->get_row("SELECT * FROM `{$wpdb->prefix}facecheck_specifications` WHERE `id`='{$_GET['specification']}'", ARRAY_A);
        }

        $editor_settings = array(
            'media_buttons' => false,
            'textarea_rows' => 5,
            'teeny' => true
        );
        ?>

        <div class="wrap">
            <form id="facecheck-specification" action="/wp-admin/admin-post.php?action=facecheck_save_specification" method="post">
                <input type="hidden" name="id" value="<?=(isset($specification) ? $specification['id'] : '')?>" />
                <label for="specification-name">Название:</label>
                <input type="text" id="specification-name" name="name" value="<?=(isset($specification) ? $specification['name'] : '')?>" placeholder="Введите название"/><br/><br/><br/>

                <label for="specification-left-title">Название слева:</label>
                <input type="text" id="specification-left-title" name="left-title" value="<?= (isset($specification) ? $specification['left_title'] : '') ?>" placeholder="Введите название" /><br/><br/>
                <label for="specification-right-title">Название справа:</label>
                <input type="text" id="specification-right-title" name="right-title" value="<?= (isset($specification) ? $specification['right_title'] : '') ?>" placeholder="Введите название" /><br/><br/><br/>

                <label for="specification-left-value">Значение слева:</label>
                <input type="text" id="specification-left-value" name="left-value" value="<?= (isset($specification) ? $specification['left_value'] : '') ?>" placeholder="Введите число" /><br/><br/>
                <label for="specification-right-value">Значение справа:</label>
                <input type="text" id="specification-right-value" name="right-value" value="<?= (isset($specification) ? $specification['right_value'] : '') ?>" placeholder="Введите число" /><br/><br/><br/>

                <label for="specification-left-text">Текст слева:</label>
                <?php wp_editor((isset($_POST['specification-left-text']) ? $_POST['specification-left-text'] : (isset($specification) ? $specification['left_text'] : '')), 'left-text', array_merge($editor_settings, array('textarea_name', 'left-text'))); ?><br/><br/>
                <label for="specification-right-text">Текст справа:</label>
                <?php wp_editor((isset($_POST['specification-right-text']) ? $_POST['specification-right-text'] : (isset($specification) ? $specification['right_text'] : '')), 'right-text', array_merge($editor_settings, array('textarea_name', 'right-text'))); ?><br/><br/>
                <input type="submit" id="specification-save" name="submit" class="button button-primary button-large" value="Сохранить" />
            </form>
        </div>

        <?php
    } else {
        if ($_POST['submit']) {
            switch($_POST['action']) {
                case 'delete' :
                    $specification = $wpdb->get_row("SELECT * FROM `{$wpdb->prefix}facecheck_specifications` WHERE `id`='{$_POST['specification']}'", ARRAY_A);
                    $wpdb->query("UPDATE `{$wpdb->prefix}facecheck_specifications` SET `position`=`position`-1 WHERE `position`>'{$specification['position']}'");
                    $wpdb->query("DELETE FROM `{$wpdb->prefix}facecheck_specifications` WHERE `id`='{$specification['id']}'");
                    break;
                case 'moveup' :
                    $specification = $wpdb->get_row("SELECT * FROM `{$wpdb->prefix}facecheck_specifications` WHERE `id`='{$_POST['specification']}'", ARRAY_A);
                    $wpdb->query("UPDATE `{$wpdb->prefix}facecheck_specifications` SET `position`='{$specification['position']}' WHERE `position`=({$specification['position']} - 1)");
                    $wpdb->update($wpdb->prefix.'facecheck_specifications', array('position' => $specification['position'] - 1), array('id' => $specification['id']));
                    break;
                case 'movedown' :
                    $specification = $wpdb->get_row("SELECT * FROM `{$wpdb->prefix}facecheck_specifications` WHERE `id`='{$_POST['specification']}'", ARRAY_A);
                    $wpdb->query("UPDATE `{$wpdb->prefix}facecheck_specifications` SET `position`='{$specification['position']}' WHERE `position`=({$specification['position']} + 1)");
                    $wpdb->update($wpdb->prefix.'facecheck_specifications', array('position' => $specification['position'] + 1), array('id' => $specification['id']));
                    break;
            }
        }

        $specifications = Facecheck::getSpecifications();
        ?>

        <table class="widefat">
            <thead>
            <th width="500px">Название</th>
            <th width="100px"></th>
            <th width="100px">Позиция</th>
            <th width="100px"></th>
            </thead>
            <tfoot>
            <th>Название секции</th>
            <th></th>
            <th>Позиция</th>
            <th colspan="2"></th>
            </tfoot>
            <tbody>
            <?php $alternate = true; ?>
            <?php foreach($specifications as $specification) { ?>
                <tr class="<?=($alternate ? 'alternate' : '')?>">
                    <?php $alternate = !$alternate ?>
                    <td>
                        <span style="display: block; margin: 15px 0 0 50px;"><?=$specification['name']?></span>
                    </td>
                    <td style="padding-top: 15px;">
                        <a href="/wp-admin/admin.php?page=facecheck/admin/specifications.php&specification=<?=$specification['id']?>" class="edit">Изменить</a>
                    </td>
                    <td style="padding-top: 15px;">
                        <a href="#" class="moveup" data-specification-id="<?=$specification['id']?>">Вверх</a>
                        <a href="#" class="movedown" data-specification-id="<?=$specification['id']?>">Вниз</a>
                    </td>
                    <td style="padding-top: 15px;">
                        <a href="#" class="delete"  data-specification-id="<?=$specification['id']?>">Удалить</a>
                    </td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
        <script type="text/javascript">
            $(function() {
                $('a.delete, a.moveup, a.movedown').click(function(e) {
                    e.preventDefault();
                    $('#facecheck-specification-action').val($(this).attr('class'));
                    $('#facecheck-specification-id').val($(this).data('specification-id'));
                    $('#facecheck-specification-submit').click();
                });
            });
        </script>
        <form id="facecheck-specifications" action="/wp-admin/admin.php?page=facecheck/admin/specifications.php" method="post">
            <input type="hidden" id="facecheck-specification-action" name="action" value="" />
            <input type="hidden" id="facecheck-specification-id" name="specification" value="" />
            <input type="submit" id="facecheck-specification-submit" name="submit" value="1" style="display:none;"/>
        </form>
        <?php
    }
?>
</div>
