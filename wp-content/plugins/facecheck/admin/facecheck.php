<div class="wrap">
    <h2>Scanface</h2>

    <form method="post" action="options.php">
        <?php settings_fields('facecheck_constants'); ?>

        <table class="form-table" style="width: 600px; margin:  0 auto;">
            <tr>
                <th scope="row" style="width: 400px;">Процент от разницы размеров глаз в пределах которого глаза считаются равными (тип глаз 1, 2)</th>
                <td><input type="text" name="facecheck_constant_eyetoeye" value="<?=get_option('facecheck_constant_eyetoeye')?>" /></td>
            </tr>
            <tr>
                <th scope="row">Процент от размера лица превышая который глаза считаются большими (тип глаз 2)</th>
                <td><input type="text" name="facecheck_constant_eyestoface" value="<?=get_option('facecheck_constant_eyestoface')?>" /></td>
            </tr>
            <tr>
                <th scope="row">Процент от высоты рта в пределах которого разница между его крайними точками считается не существенной (тип рта 1, 2)</th>
                <td><input type="text" name="facecheck_constant_mlrtombt" value="<?=get_option('facecheck_constant_mlrtombt')?>" /></td>
            </tr>
            <tr>
                <td colspan="2">
                    <input type="submit" class="button-primary" value="Сохранить" style="float: right; margin-right: 25px;"/>
                </td>
            </tr>
        </table>
    </form>
</div>