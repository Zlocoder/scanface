<?php
    if (isset($_POST['action']) && $_POST['action'] == 'delete') {
        $wpdb->delete($wpdb->prefix.'facecheck_users', array('id' => $_POST['user']));
    }
?>

<div class="wrap">
    <?php $users = $wpdb->get_results("SELECT * FROM `{$wpdb->prefix}facecheck_users` ORDER BY `date` DESC", ARRAY_A); ?>

    <h2>
        Пользователи
        <!--<a href="/wp-admin/admin.php?page=facecheck/admin/professions.php&new" class="add-new-h2">Добавить новую</a>-->
        <span style="font-weight: normal;">(всего: <?= $wpdb->get_var("SELECT COUNT(*) FROM `{$wpdb->prefix}facecheck_users`"); ?>)</span>
    </h2>

    <table class="widefat">
        <thead>
        <th>Email</th>
        <th>Имя, фамилия</th>
        <th width="100px" colspan="2">Действия</th>
        </thead>
        <tfoot>
        <th>Email</th>
        <th>Имя, фамилия</th>
        <th width="100px" colspan="2">Действия</th>
        </tfoot>
        <tbody>
        <?php $alternate = true; ?>
        <?php foreach($users as $user) { ?>
            <tr class="<?=($alternate ? 'alternate' : '')?>">
                <?php $alternate = !$alternate ?>
                <td>
                    <span style="display: block; margin: 15px 0 0 50px;"><?=$user['email']?></span>
                </td>
                <td>
                    <span style="display: block; margin: 15px 0 0 50px;"><?=$user['name']?></span>
                </td>
                <td style="padding-top: 15px;">
                    <a href="#" class="delete"  data-profession-id="<?=$user['id']?>">Удалить</a>
                </td>
            </tr>
        <?php } ?>
        </tbody>
    </table>
    <script type="text/javascript">
        $(function() {
            $('a.delete').click(function(e) {
                e.preventDefault();
                $('#facecheck-user-action').val('delete');
                $('#facecheck-user-id').val($(this).data('profession-id'));
                $('#facecheck-user-submit').click();
            });
        });
    </script>
    <form id="facecheck-sections" action="/wp-admin/admin.php?page=facecheck/admin/users.php" method="post">
        <input type="hidden" id="facecheck-user-action" name="action" value="" />
        <input type="hidden" id="facecheck-user-id" name="user" value="" />
        <input type="submit" id="facecheck-user-submit" name="submit" value="1" style="display:none;"/>
    </form>
</div>