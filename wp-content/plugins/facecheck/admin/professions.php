<div class="wrap">
    <h2>
        Профессии
        <a href="/wp-admin/admin.php?page=facecheck/admin/professions.php&new" class="add-new-h2">Добавить новую</a>
    </h2>

    <?php
        if (isset($_GET['new']) || isset($_GET['profession'])) {
            if (isset($_GET['new'])) {
                $specifications = Facecheck::getSpecifications();
            } else {
                $prof = $wpdb->get_row("SELECT * FROM `{$wpdb->prefix}facecheck_professions` WHERE `id` = {$_GET['profession']}", ARRAY_A);
                $specifications = Facecheck::getSpecifications($prof['id']);
            }
            $categories = Facecheck::getProfessionCategories();
            ?>

            <div class="wrap">
                <form id="facecheck-profession" action="/wp-admin/admin-post.php?action=facecheck_save_profession" method="post">
                    <input type="hidden" name="id" value="<?=(isset($prof) ? $prof['id'] : '')?>" />
                    <label for="profession-name"><b>Профессия:</b></label><br/>
                    <input type="text" id="profession-name" name="name" value="<?=(isset($prof) ? $prof['name'] : '')?>" placeholder="Введите название" style="width: 350px;"/><br/><br/>

                    <label for="profession-category"><b>Группа:</b></label><br/>
                    <select id="profession-category" name="category">
                        <?php foreach ($categories as $id => $category) { ?>
                            <?php if ($prof['id_category'] == $id) { ?>
                                <option value="<?= $id ?>" selected ><?= $category['name'] ?></option>
                            <?php } else { ?>
                                <option value="<?= $id ?>" ><?= $category['name'] ?></option>
                            <?php } ?>
                        <?php } ?>
                    </select>

                    <?php foreach ($specifications as $spec) { ?>
                        <div class="specification" data-left-value="<?= $spec['left_value'] ?>" data-right-value="<?= $spec['right_value'] ?>">
                            <?php
                                if (empty($spec['prof_right'])) {
                                    $spec['prof_right'] = $spec['prof_left'] + 3;
                                }
                                $lpercent = $spec['prof_left'] * 100 / ($spec['right_value'] - $spec['left_value']);
                                $rpercent = $spec['prof_right'] * 100 / ($spec['right_value'] - $spec['left_value']);
                            ?>
                            <b><?= $spec['name'] ?> (<?= $spec['left_value'] ?> - <?= $spec['right_value'] ?>)</b>
                            <div class="progress">
                                <div class="diapason" style="left: <?= $lpercent ?>%; width: <?= $rpercent - $lpercent ?>%;"></div>
                            </div>
                            <div class="scale">
                                <ul>
                                    <li></li>
                                    <li>1</li>
                                    <li>2</li>
                                    <li>3</li>
                                    <li>4</li>
                                    <li>5</li>
                                    <li>6</li>
                                    <li>7</li>
                                    <li>8</li>
                                    <li>9</li>
                                </ul>
                            </div>
                            <br/><br/>

                            <div class="values">
                                <label for="specification_<?= $spec['id'] ?>_left">От</label>
                                <input type="text" class="value_left" name="specification[<?= $spec['id'] ?>][left]" value="<?= $spec['prof_left'] ?>" />

                                &nbsp;&nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp;&nbsp;

                                <label for="specification_<?= $spec['id'] ?>_right">До</label>
                                <input type="text" class="value_right" name="specification[<?= $spec['id'] ?>][right]" value="<?= $spec['prof_right'] ?>" />
                            </div>
                            <br/>
                            <textarea name="specification[<?= $spec['id'] ?>][descr]" style="width: 350px; height: 80px;"><?= $spec['prof_desc']?></textarea>
                        </div>
                    <?php } ?>

                    <input type="submit" id="profession-save" name="submit" class="button button-primary button-large" value="Сохранить" />
                </form>
            </div>
            <script type="text/javascript">
                jQuery(function() {
                    jQuery('.specification .diapason').draggable({
                        axis: 'x',
                        grid: [34,0],
                        containment: 'parent',
                        drag: function(event, ui) {
                            var left_value = ui.position.left / 34;
                            var right_value = left_value + jQuery(this).width() / 34;
                            jQuery(this).parents('.specification').find('.value_left').val(left_value);
                            jQuery(this).parents('.specification').find('.value_right').val(right_value);
                        }
                    });

                    jQuery('.specification .values input').change(function() {
                        var $specification = jQuery(this).parents('.specification');
                        var lpercent = $specification.find('.value_left').val() * 100 / ($specification.data('right-value') - $specification.data('left-value'));
                        var rpercent = $specification.find('.value_right').val() * 100 / ($specification.data('right-value') - $specification.data('left-value'));
                        $specification.find('.diapason').css({left: lpercent + '%','width' : rpercent - lpercent + '%'});
                    });
                });
            </script>

            <?php
        } elseif (isset($_POST['action']) && $_POST['action'] == 'delete') {
            $wpdb->delete($wpdb->prefix.'facecheck_professions', array('id' => $_POST['profession']));
            $wpdb->delete($wpdb->prefix.'facecheck_professions_specifications', array('id_profession' => $_POST['profession']));
        } else {
            $profs = Facecheck::getProfessions();
            ?>

            <table class="widefat">
                <thead>
                    <th>Профессия</th>
                    <th width="100px" colspan="2">Действия</th>
                </thead>
                <tfoot>
                    <th>Профессия</th>
                    <th width="100px" colspan="2">Действия</th>
                </tfoot>
                <tbody>
                    <?php $alternate = true; ?>
                    <?php foreach($profs as $prof) { ?>
                        <tr class="<?=($alternate ? 'alternate' : '')?>">
                            <?php $alternate = !$alternate ?>
                            <td>
                                <span style="display: block; margin: 15px 0 0 50px;"><?=$prof['name']?></span>
                            </td>
                            <td style="padding-top: 15px;">
                                <a href="/wp-admin/admin.php?page=facecheck/admin/professions.php&profession=<?=$prof['id']?>" class="edit">Изменить</a>
                            </td>
                            <td style="padding-top: 15px;">
                                <a href="#" class="delete"  data-profession-id="<?=$prof['id']?>">Удалить</a>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
            <script type="text/javascript">
                $(function() {
                    $('a.delete').click(function(e) {
                        e.preventDefault();
                        $('#facecheck-profession-action').val('delete');
                        $('#facecheck-profession-id').val($(this).data('profession-id'));
                        $('#facecheck-profession-submit').click();
                    });
                });
            </script>
            <form id="facecheck-sections" action="/wp-admin/admin.php?page=facecheck/admin/professions.php" method="post">
                <input type="hidden" id="facecheck-profession-action" name="action" value="" />
                <input type="hidden" id="facecheck-profession-id" name="profession" value="" />
                <input type="submit" id="facecheck-profession-submit" name="submit" value="1" style="display:none;"/>
            </form>
    <?php } ?>
</div>