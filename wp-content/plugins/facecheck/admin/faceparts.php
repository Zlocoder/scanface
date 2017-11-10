<?php

$faceparts = Facecheck::getFaceparts();

?>

<div class="wrap">
    <h2>Части лица</h2>
    <table class="widefat">
        <?php foreach ($faceparts as $facepart) : ?>
            <tr></tr>
        <?php endforeach; ?>
    </table>
</div>