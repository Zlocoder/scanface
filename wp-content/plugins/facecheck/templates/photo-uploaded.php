<li data-id="<?= $id ?>" data-process="<?= $process ?>">
    <div class="img">
        <img src="<?= content_url("/photos/photo_$id.jpg") ?>" />
    </div>
    <? if ($process) : ?>
        <div class="loader">
            <span>Обработка...</span>
        </div>
    <? endif; ?>
    <ul class="buttons" <?= $process ? 'style="display: none;"' : '' ?>>
        <li>
            <a href="<?= add_query_arg('facecheck_photo_id', $id, get_permalink(get_page_by_title('Markers'))) ?>" class="edit">Редактировать маркеры</a>
        </li>
        <li>
            <a href="#">Удалить</a>
        </li>
    </ul>
</li>