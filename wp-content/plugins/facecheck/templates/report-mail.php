<div>
    <img src="<?=content_url('/photos/face_'.$photo->id.'.jpg')?>" style="width: 200px; height: auto; margin-right: 20px; float: left;"/>

    <div style="margin-left: 220px;">
        <div style="margin-right: 120px; float: left;">
            <div style="margin-bottom: 10px;">
                <span style="display: inline-block; width: 150px; color: #666;">Дата тестирования:</span>
                <span style="color: #333; font-weight: bold;"><?=$photo->date?></span>
            </div>

            <div style="margin-bottom: 10px;">
                <span style="display: inline-block; width: 150px; color: #666;">Имя тестируемого:</span>
                <span style="color: #333; font-weight: bold"><?=($photo->name ? $photo->name : 'без имени')?></span>
            </div>

            <?php if ($photo->comment) { ?>
                <div style="display: inline-block; width: 150px; color: #666;">Примечание:</div>
                <div style="color: #333; font-weight: bold;"><?=$photo->comment?></div>
            <?php } ?>
        </div>

        <img src="http://www.scanface.com.ua/wp-content/themes/salient/img/shtamp.png" style="width: 100px; height: 100px; float: left; position: absolute; bottom: 70px;" />
    </div>
</div>

<div style="clear: both; margin-bottom: 20px;"></div>

<div>
    <p style="font-size: 14px;">
        Здравствуйте!
    </p>
    <p style="font-size: 14px;">
        Вы получили это письмо, потому что запросили отправку на этот почтовый ящик результата сканирования<?= ($photo->name) ? ' для ' . $photo->name : '' ?>.
        <br/>
        Смотрите отчет в прикрепленном файле.
    </p>

    <p style="font-size: 12px;">
        Это письмо сгенерировано автоматически, пожалуйста не отвечайте на него.
    </p>
</div>

