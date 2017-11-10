<div class="wrap">
    <h2>
        Секции отчетов
        <a href="/wp-admin/admin.php?page=facecheck/admin/sections.php&new" class="add-new-h2">Добавить новую</a>
    </h2>

    <?php
        if (isset($_GET['section']) || isset($_GET['new'])) {
            if (isset($_GET['section'])) {
                $section = $wpdb->get_row("SELECT * FROM `{$wpdb->prefix}facecheck_report_sections` WHERE `id`='{$_GET['section']}'", ARRAY_A);
            }
            ?>

            <div class="wrap">
                <form id="facecheck-section" action="/wp-admin/admin-post.php?action=facecheck_save_section" method="post">
                    <input type="hidden" name="id" value="<?=(isset($section) ? $section['id'] : '')?>" />
                    <label for="section-name">Заголовок:</label>
                    <input type="text" id="section-name" name="name" value="<?=(isset($section) ? $section['name'] : '')?>" placeholder="Введите название"/><br/>
                    <label for="section-icon">Иконка</label>
                    <img id="section-icon" src="<?=((isset($section) && file_exists(WP_CONTENT_DIR . '/plugins/facecheck/sicons/' . $section['id'] . '.jpg')) ? plugins_url('facecheck/sicons/') . $section['id'] . '.jpg' : plugins_url('facecheck/sicons/') . 'upload.jpg')?>" />
                    <input type="submit" id="section-save" name="submit" class="button button-primary button-large" value="Сохранить" />
                </form>
                <script type="text/javascript">
                    $(function() {
                        var $input = $('<input type="file" name="sicon" accept="image/*" />');

                        $input.fileupload({
                            url: '/wp-admin/admin-ajax.php?action=facecheck_upload_sicon',
                            dataType: 'text',
                            dropZone: $('#section-icon'),
                            fileInput: $input,
                            limitMultiFileUploads: 1,
                            done: function(e, data) {
                                if (data.result) {
                                    $('#section-icon').attr('src', data.result);
                                } else {
                                    alert('upload error');
                                }
                            }
                        });

                        $('#section-icon').click(function() {
                            if (!$input.get(0).files || !$input.get(0).files.length) {
                                $input.click();
                            }
                        })
                    })
                </script>
            </div>

            <?php
        } else {
            if ($_POST['submit']) {
                switch($_POST['action']) {
                    case 'delete' :
                        $section = $wpdb->get_row("SELECT * FROM `{$wpdb->prefix}facecheck_report_sections` WHERE `id`='{$_POST['section']}'", ARRAY_A);
                        $wpdb->query("UPDATE `{$wpdb->prefix}facecheck_report_sections` SET `position`=`position`-1 WHERE `position`>'{$section['position']}'");
                        $wpdb->query("DELETE FROM `{$wpdb->prefix}facecheck_report_sections` WHERE `id`='{$section['id']}'");
                        $wpdb->query("DELETE FROM `{$wpdb->prefix}facecheck_reports` WHERE `section_id`='{$section['id']}'");
                        break;
                    case 'moveup' :
                        $section = $wpdb->get_row("SELECT * FROM `{$wpdb->prefix}facecheck_report_sections` WHERE `id`='{$_POST['section']}'", ARRAY_A);
                        $wpdb->query("UPDATE `{$wpdb->prefix}facecheck_report_sections` SET `position`='{$section['position']}' WHERE `position`=({$section['position']} - 1)");
                        $wpdb->update($wpdb->prefix.'facecheck_report_sections', array('position' => $section['position'] - 1), array('id' => $section['id']));
                        break;
                    case 'movedown' :
                        $section = $wpdb->get_row("SELECT * FROM `{$wpdb->prefix}facecheck_report_sections` WHERE `id`='{$_POST['section']}'", ARRAY_A);
                        $wpdb->query("UPDATE `{$wpdb->prefix}facecheck_report_sections` SET `position`='{$section['position']}' WHERE `position`=({$section['position']} + 1)");
                        $wpdb->update($wpdb->prefix.'facecheck_report_sections', array('position' => $section['position'] + 1), array('id' => $section['id']));
                        break;
                }
            }

            $sections = Facecheck::getSections();
            ?>

            <table class="widefat">
                <thead>
                <th width="500px">Название секции</th>
                <th width="100px"></th>
                <th width="100px">Позиция</th>
                <th width="100px" colspan="2"></th>
                </thead>
                <tfoot>
                <th>Название секции</th>
                <th></th>
                <th>Позиция</th>
                <th colspan="2"></th>
                </tfoot>
                <tbody>
                <?php $alternate = true; ?>
                <?php foreach($sections as $section) { ?>
                    <tr class="<?=($alternate ? 'alternate' : '')?>">
                        <?php $alternate = !$alternate ?>
                        <td>
                            <img src="<?=(file_exists(WP_CONTENT_DIR . '/plugins/facecheck/sicons/' . $section['id'] . '.jpg') ? plugins_url('facecheck/sicons/' . $section['id'] . '.jpg') : '')?>" width="40px" height="40px" style="float:left;"/>
                            <span style="display: block; margin: 15px 0 0 50px;"><?=$section['name']?></span>
                        </td>
                        <td style="padding-top: 15px;">
                            <a href="/wp-admin/admin.php?page=facecheck/admin/sections.php&section=<?=$section['id']?>" class="edit">Изменить</a>
                        </td>
                        <td style="padding-top: 15px;">
                            <a href="#" class="moveup" data-section-id="<?=$section['id']?>">Вверх</a>
                            <a href="#" class="movedown" data-section-id="<?=$section['id']?>">Вниз</a>
                        </td>
                        <td style="padding-top: 15px;">
                            <a href="#" class="delete"  data-section-id="<?=$section['id']?>">Удалить</a>
                        </td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
            <script type="text/javascript">
                $(function() {
                    $('a.delete, a.moveup, a.movedown').click(function(e) {
                        e.preventDefault();
                        $('#facecheck-section-action').val($(this).attr('class'));
                        $('#facecheck-section-id').val($(this).data('section-id'));
                        $('#facecheck-section-submit').click();
                    });
                });
            </script>
            <form id="facecheck-sections" action="/wp-admin/admin.php?page=facecheck/admin/sections.php" method="post">
                <input type="hidden" id="facecheck-section-action" name="action" value="" />
                <input type="hidden" id="facecheck-section-id" name="section" value="" />
                <input type="submit" id="facecheck-section-submit" name="submit" value="1" style="display:none;"/>
            </form>
    <?php } ?>
</div>

