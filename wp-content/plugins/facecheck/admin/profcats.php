<div class="wrap">
    <h2>
        Группы профессий
        <a href="/wp-admin/admin.php?page=facecheck/admin/sections.php&new" class="add-new-h2">Добавить новую</a>
    </h2>

    <?php
    if (isset($_GET['category']) || isset($_GET['new'])) {
        if (isset($_GET['category'])) {
            $category = $wpdb->get_row("SELECT * FROM `{$wpdb->prefix}facecheck_profession_categories` WHERE `id`='{$_GET['category']}'", ARRAY_A);
        }
        ?>

        <div class="wrap">
            <form id="facecheck-profession-category" action="/wp-admin/admin-post.php?action=facecheck_save_profession_category" method="post">
                <input type="hidden" name="id" value="<?=(isset($category) ? $category['id'] : '')?>" />
                <label for="category-name">Название:</label>
                <input type="text" id="name" name="name" value="<?=(isset($category) ? $category['name'] : '')?>" placeholder="Введите название"/><br/><br/>
                <!--
                <label for="category-icon">Иконка</label>
                <img id="category-icon" src="<?=((isset($category) && file_exists(WP_CONTENT_DIR . '/plugins/facecheck/pcicons/' . $category['id'] . '.jpg')) ? plugins_url('facecheck/pcicons/') . $category['id'] . '.jpg' : plugins_url('facecheck/pcicon/') . 'upload.jpg')?>" />
                -->
                <input type="submit" id="category-save" name="submit" class="button button-primary button-large" value="Сохранить" />
            </form>
            <script type="text/javascript">
                $(function() {
                    var $input = $('<input type="file" name="pcicon" accept="image/*" />');

                    $input.fileupload({
                        url: '/wp-admin/admin-ajax.php?action=facecheck_upload_pcicon',
                        dataType: 'text',
                        dropZone: $('#category-icon'),
                        fileInput: $input,
                        limitMultiFileUploads: 1,
                        done: function(e, data) {
                            if (data.result) {
                                $('#category-icon').attr('src', data.result);
                            } else {
                                alert('upload error');
                            }
                        }
                    });

                    $('#category-icon').click(function() {
                        if (!$input.get(0).files || !$input.get(0).files.length) {
                            $input.click();
                        }
                    });
                })
            </script>
        </div>

    <?php
    } else {
        if ($_POST['submit']) {
            switch($_POST['action']) {
                case 'delete' :
                    $category = $wpdb->get_row("SELECT * FROM `{$wpdb->prefix}facecheck_profession_categories` WHERE `id`='{$_POST['category']}'", ARRAY_A);
                    $wpdb->query("DELETE FROM `{$wpdb->prefix}facecheck_professions` WHERE `id_category`='{$category['id']}'");
                    $wpdb->query("DELETE FROM `{$wpdb->prefix}facecheck_profession_categories` WHERE `id`='{$category['id']}'");
                    break;
            }
        }

        $categories = Facecheck::getProfessionCategories();
        ?>

        <table class="widefat">
            <thead>
                <th width="500px">Группа</th>
                <th width="100px" colspan="2">Действия</th>
            </thead>
            <tfoot>
                <th>Группа</th>
                <th colspan="2">Действия</th>
            </tfoot>
            <tbody>
            <?php $alternate = true; ?>
            <?php foreach($categories as $category) { ?>
                <tr class="<?=($alternate ? 'alternate' : '')?>">
                    <?php $alternate = !$alternate ?>
                    <td>
                        <!--<img src="<? /*(file_exists(WP_CONTENT_DIR . '/plugins/facecheck/pccons/' . $section['id'] . '.jpg') ? plugins_url('facecheck/pccons/' . $section['id'] . '.jpg') : '') */ ?>" width="40px" height="40px" style="float:left;"/>-->
                        <span style="display: block; margin: 15px 0 0 50px;"><?=$category['name']?></span>
                    </td>
                    <td style="padding-top: 15px;">
                        <a href="/wp-admin/admin.php?page=facecheck/admin/profcats.php&category=<?=$category['id']?>" class="edit">Изменить</a>
                    </td>
                    <td style="padding-top: 15px;">
                        <a href="#" class="delete"  data-category-id="<?=$category['id']?>">Удалить</a>
                    </td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
        <script type="text/javascript">
            $(function() {
                $('a.delete, a.moveup, a.movedown').click(function(e) {
                    e.preventDefault();
                    $('#facecheck-category-action').val($(this).attr('class'));
                    $('#facecheck-category-id').val($(this).data('section-id'));
                    $('#facecheck-category-submit').click();
                });
            });
        </script>
        <form id="facecheck-sections" action="/wp-admin/admin.php?page=facecheck/admin/profcats.php" method="post">
            <input type="hidden" id="facecheck-category-action" name="action" value="" />
            <input type="hidden" id="facecheck-category-id" name="section" value="" />
            <input type="submit" id="facecheck-category-submit" name="submit" value="1" style="display:none;"/>
        </form>
    <?php } ?>
</div>

