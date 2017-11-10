<?php

require_once ABSPATH . 'wp-includes/unirest/Unirest.php';

class Facecheck {
    private static $_session;

    public static function install() {
        global $wpdb;

        /*
        $wpdb->query("
            CREATE TABLE `{$wpdb->prefix}facecheck_professions`
            (
              `id` INT NOT NULL AUTO_INCREMENT,
              `name` VARCHAR(255) NOT NULL,
              PRIMARY KEY (`id`)
            )
            AUTO INCREMENT = 1
        ");

        $wpdb->query("
            CREATE TABLE `{$wpdb->prefix}facecheck_users`
            (
                `id` INT NOT NULL AUTO_INCREMENT,
                `email` VARCHAR(128) NOT NULL,
                `password` VARCHAR(32) NOT NULL,
                `name` VARCHAR(128) NOT NULL,
                `date_birth` DATE NOT NULL,
                `date` DATETIME NOT NULL,
                PRIMARY KEY (`id`)
            )
            AUTO_INCREMENT = 1
        ");

        $wpdb->query("
            CREATE TABLE `{$wpdb->prefix}facecheck_photos`
            (
                `id` INT NOT NULL AUTO_INCREMENT,
                `user_id` INT NOT NULL,
                `img` VARCHAR(36) NOT NULL,
                `face` VARCHAR(36) NOT NULL,
                `markers` TEXT NOT NULL,
                `date` DATETIME NOT NULL,
                `name` VARCHAR(128) NOT NULL,
                `comment` TEXT NOT NULL,
                PRIMARY KEY (`id`)
            )
            AUTO_INCREMENT = 1
        ");

        $wpdb->query("
            CREATE TABLE `{$wpdb->prefix}facecheck_faceparts`
            (
                `id` INT NOT NULL AUTO_INCREMENT,
                `name` VARCHAR(128) NOT NULL,
                PRIMARY KEY (`id`)
            )
            AUTO_INCREMENT = 1
        ");

        $wpdb->query("INSERT INTO `{$wpdb->prefix}facecheck_faceparts` (`name`) VALUES ('Брови'), ('Глаза'), ('Рот')");

        $wpdb->query("
            CREATE TABLE `{$wpdb->prefix}facecheck_facepart_types`
            (
                `id` INT NOT NULL AUTO_INCREMENT,
                `id_facepart` INT NOT NULL,
                `name` VARCHAR(255) NOT NULL,
                PRIMARY KEY (`id`)
            )
            AUTO_INCREMENT = 1
        ");

        $wpdb->query("INSERT INTO `{$wpdb->prefix}facecheck_facepart_types` (`id_facepart`, `name`) VALUES
            ('1', 'Тип 1'), ('1', 'Тип 2'), ('1', 'Тип 3'), ('1', 'Тип 4'),
            ('2', 'Тип 1'), ('2', 'Тип 2'), ('2', 'Тип 3'), ('2', 'Тип 4'),
            ('3', 'Тип 1'), ('3', 'Тип 2'), ('3', 'Тип 3'), ('3', 'Тип 4')
        ");

        $wpdb->query("
            CREATE TABLE `{$wpdb->prefix}facecheck_report_sections`
            (
                `id` INT NOT NULL AUTO_INCREMENT,
                `name` VARCHAR(255) NOT NULL,
                `position` INT NOT NULL,
                PRIMARY KEY (`id`)
            )
            AUTO_INCREMENT = 1
        ");

        $wpdb->query("
            CREATE TABLE `{$wpdb->prefix}facecheck_reports`
            (
                `faceparts` TEXT NOT NULL,
                `section_id` INT NOT NULL,
                `text` TEXT NOT NULL
            )
        ");

        $wpdb->query("
            CREATE TABLE `{$wpdb->prefix}facecheck_user_categories`
            (
                `id` INT NOT NULL AUTO_INCREMENT,
                `user_id` INT NOT NULL,
                `name` VARCHAR(128) NOT NULL,
                `date` DATETIME NOT NULL,
                PRIMARY KEY (`id`)
            )
            AUTO_INCREMENT = 1
        ");

        $wpdb->query("
            CREATE TABLE `{$wpdb->prefix}facecheck_photo_category`
            (
                `photo_id` INT NOT NULL,
                `category_id` INT NOT NULL
            )
        ");

        mkdir(WP_CONTENT_DIR . '/photos');
        */
    }

    public static function uninstall() {
        global $wpdb;
        /*
        $wpdb->query("DROP TABLE `{$wpdb->prefix}facecheck_users`");
        $wpdb->query("DROP TABLE `{$wpdb->prefix}facecheck_photos`");
        $wpdb->query("DROP TABLE `{$wpdb->prefix}facecheck_faceparts`");
        $wpdb->query("DROP TABLE `{$wpdb->prefix}facecheck_facepart_types`");
        $wpdb->query("DROP TABLE `{$wpdb->prefix}facecheck_report_sections`");
        $wpdb->query("DROP TABLE `{$wpdb->prefix}facecheck_reports`");
        $wpdb->query("DROP TABLE `{$wpdb->prefix}facecheck_user_categories`");
        $wpdb->query("DROP TABLE `{$wpdb->prefix}facecheck_photo_category`");

        $dir = opendir(WP_CONTENT_DIR . '/photos');
        while($file = readdir($dir)) {
            if ($file != '.' && $file != '..') {
                unlink(WP_CONTENT_DIR . '/photos/' . $file);
            }
        }

        closedir($dir);
        rmdir(WP_CONTENT_DIR . '/photos');
        */
    }

    public static function init() {
        if (session_id() == "") {
            session_set_cookie_params(2592000);
            session_start();
            //setcookie(session_name(),session_id(),time()+2592000);
        }

        if (!isset($_SESSION['facecheck'])) {
            $_SESSION['facecheck'] = array();
        }
        self::$_session =& $_SESSION['facecheck'];
    }

    public static function getFaceparts() {
        global $wpdb;

        $rows = $wpdb->get_results("SELECT * FROM `{$wpdb->prefix}facecheck_faceparts` ORDER BY `id`", ARRAY_A);
        $result = array();

        foreach ($rows as $row) {
            $result[$row['id']] = $row;
        }

        return $result;
    }

    public static function getFacepartTypes() {
        global $wpdb;

        $rows =  $wpdb->get_results("SELECT * FROM `{$wpdb->prefix}facecheck_facepart_types` ORDER BY `id`", ARRAY_A);
        $result = array();

        foreach ($rows as $row) {
            $result[$row['id']] = $row;
        }

        return $result;
    }

    public static function getSections() {
        global $wpdb;

        $rows = $wpdb->get_results("SELECT * FROM `{$wpdb->prefix}facecheck_report_sections` ORDER BY `position`", ARRAY_A);
        $result = array();

        foreach ($rows as $row) {
            $result[$row['id']] = $row;
        }

        return $result;
    }

    public static function getProfessionCategories() {
        global $wpdb;

        $rows = $wpdb->get_results("SELECT * FROM `{$wpdb->prefix}facecheck_profession_categories` ORDER BY `name`", ARRAY_A);

        $result = array();
        foreach ($rows as $row) {
            $result[$row['id']] = $row;
        }

        return $result;
    }

    public static function getProfessions($user = 0) {
        global $wpdb;

        if ($user) {
            $rows = $wpdb->get_results("
                SELECT *
                FROM `{$wpdb->prefix}facecheck_professions`
                WHERE `id_user` = 0 OR `id_user` = $user
                ORDER BY `id_category`, `id_user`, `name`
            ", ARRAY_A);
        } else {
            $rows = $wpdb->get_results("
                SELECT * FROM `{$wpdb->prefix}facecheck_professions`
                WHERE `id_user` = 0
                ORDER BY `id_category`, `name`
            ", ARRAY_A);
        }

        $result = array();
        foreach ($rows as $row) {
            $result[$row['id']] = $row;
        }

        return $result;
    }

    public static function getSpecifications($profession = 0) {
        global $wpdb;

        $rows = $wpdb->get_results("
            SELECT *, 0 AS `prof_left`, 0 AS `prof_right`, '' AS `prof_desc`
            FROM `{$wpdb->prefix}facecheck_specifications`
        ", ARRAY_A);

        $result = array();

        foreach ($rows as $row) {
            $result[$row['id']] = $row;
        }

        $prows = array();
        if ($profession) {
            $rows = $wpdb->get_results("
                SELECT `s`.*, `ps`.`left_value` AS `prof_left`, `ps`.`right_value` AS `prof_right`, `ps`.`description` AS `prof_desc`
                FROM `{$wpdb->prefix}facecheck_specifications` AS `s`
                LEFT JOIN `{$wpdb->prefix}facecheck_professions_specifications` AS `ps` ON `ps`.`id_specification` = `s`.`id`
                WHERE `ps`.`id_profession` = $profession
            ", ARRAY_A);

            foreach ($rows as $row) {
                $prows[$row['id']] = $row;
            }
        }

        foreach($result as $id => $row) {
            if (isset($prows[$id])) {
                $result[$id]['prof_left'] = $prows[$id]['prof_left'];
                $result[$id]['prof_right'] = $prows[$id]['prof_right'];
                $result[$id]['prof_desc'] = $prows[$id]['prof_desc'];
            } else {
                $result[$id]['prof_left'] = 0;
                $result[$id]['prof_right'] = 0;
                $result[$id]['prof_desc'] = '';
            }
        }

        return $result;
    }

    public static function getCustomSpecifications($profession) {
        global $wpdb;

        $result = array();

        $rows = $wpdb->get_results("
            SELECT `id_specification`, `left`, `right`
            FROM `{$wpdb->prefix}facecheck_custom_specifications`
            WHERE `id_user` = '{$_SESSION['facecheck_user']->id}' AND `id_profession` = $profession
        ", ARRAY_A);

        foreach ($rows as $row) {
            $result[$row['id_specification']] = $row;
        }

        return $result;
    }

    public static function saveReport($faceparts, $text, $specification) {
        global $wpdb;

        if (is_array($faceparts)) {
            $faceparts = json_encode($faceparts);
        }

        $sections = self::getSections();

        foreach ($sections as $section) {
            if ($wpdb->get_row("SELECT `id` FROM `{$wpdb->prefix}` WHERE `faceparts`='{$faceparts}' AND `section_id`='{$section['id']}'")) {
                $wpdb->update($wpdb->prefix.'facecheck_reports', array('section_id' => $section['id'], 'text' => wp_kses_stripslashes($text[$section['id']])), array('faceparts' => $faceparts));
            } else {
                $wpdb->insert($wpdb->prefix.'facecheck_reports', array('faceparts' => $faceparts, 'section_id' => $section['id'], 'text' => wp_kses_stripslashes($text[$section['id']])));
            }
        }

        foreach ($specification as $id => $value) {
            if ($wpdb->get_row("SELECT * FROM `{$wpdb->prefix}facecheck_reports_specifications` WHERE `specification_id` = {$id} AND `faceparts` = '$faceparts'")) {
                $wpdb->update($wpdb->prefix.'facecheck_reports_specifications', array('value' => $value), array('faceparts' => $faceparts, 'specification_id' => $id));
            } else {
                $wpdb->insert($wpdb->prefix.'facecheck_reports_specifications', array('faceparts' => $faceparts, 'specification_id' => $id, 'value' => $value));
            }
        }
    }

    public static function getReport($faceparts) {
        global $wpdb;

        if (is_array($faceparts)) {
            $faceparts = json_encode($faceparts);
        }

        $rows1 = $wpdb->get_results("SELECT * FROM `{$wpdb->prefix}facecheck_reports` WHERE `faceparts`='{$faceparts}' ORDER BY `section_id`", ARRAY_A);
        $rows2 = $wpdb->get_results("SELECT * FROM `{$wpdb->prefix}facecheck_reports_specifications` WHERE `faceparts` = '{$faceparts}'", ARRAY_A);
        $rows3 = $wpdb->get_results("SELECT * FROM `{$wpdb->prefix}facecheck_specifications`", ARRAY_A);

        $specifications = array();
        foreach ($rows3 as $row) {
            $specifications[$row['id']] = $row;
        }

        foreach ($rows2 as $row) {
            $specifications[$row['specification_id']]['value'] = $row['value'];
        }

        foreach ($specifications as $id => $specification) {
            if (!isset($specification['value'])) {
                $specifications[$id]['value'] = '';
            }
        }

        $report = array(
            'sections' => array(),
            'specifications' => array()
        );

        foreach ($rows1 as $row) {
            $report['sections'][$row['section_id']] = $row;
        }

        $report['specifications'] = $specifications;

        return $report;
    }

    public static function addPhoto($source) {
        global $wpdb;

        $is_file = is_file($source);
        $imagefile_data = base64_encode($is_file ? file_get_contents($source) : $source);

        ob_start();
            include __DIR__ . '/../templates/requests/uploadNewImage_File.xml.php';
        $request = ob_get_clean();

        $response = Unirest::post(
            'http://www.betafaceapi.com/service.svc/UploadNewImage_File',
            array(
                "Content-Type" => "application/xml"
            ),
            $request
        );

        $response = new SimpleXMLElement($response->body);
        $img = $response->img_uid->__toString();

        $wpdb->insert(
            $wpdb->prefix . 'facecheck_photos',
            array(
                'img' => $img,
                'user_id' => isset($_SESSION['facecheck_user']) ? $_SESSION['facecheck_user']->id : '0',
                'date' => '0000-00-00 00:00:00'
            )
        );

        $id = $wpdb->insert_id;

        if ($is_file) {
            move_uploaded_file($source, WP_CONTENT_DIR . '/photos/photo_' . $id . '.jpg');
        } else {
            file_put_contents(WP_CONTENT_DIR . '/photos/photo_' . $id . '.jpg', $source);
        }

        return $id;
    }

    public static function deletePhoto($id) {
        global $wpdb;

        if (file_exists(WP_CONTENT_DIR . '/photos/photo_' . $id . '.jpg')) {
            unlink(WP_CONTENT_DIR . '/photos/photo_' . $id . '.jpg');
        }
        if (file_exists(WP_CONTENT_DIR . '/photos/face_' . $id . '.jpg')) {
            unlink(WP_CONTENT_DIR . '/photos/face_' . $id . '.jpg');
        }

        $wpdb->delete($wpdb->prefix . 'facecheck_photos', array('id' => $id));
        $wpdb->delete($wpdb->prefix . 'facecheck_photo_category', array('photo_id' => $id));
    }

    public static function getMarkers($id, $reset = false) {
        global $wpdb;

        list($img, $face, $markers) = $wpdb->get_row("SELECT `img`, `face`, `markers` FROM `{$wpdb->prefix}facecheck_photos` WHERE `id`='{$id}'", ARRAY_N);

        if (empty($face)) {
            ob_start();
                include __DIR__ . '/../templates/requests/getImageInfo.xml.php';
            $request = ob_get_clean();

            $response = Unirest::post(
                'http://www.betafaceapi.com/service.svc/GetImageInfo',
                array(
                    "Content-Type" => "application/xml"
                ),
                $request
            );

            $response = new SimpleXMLElement($response->body);
            if ($response->int_response->__toString() == '1') {
                return false;
            }

            if (isset($response->faces->FaceInfo)) {
                $face = $response->faces->FaceInfo->uid->__toString();
                $wpdb->update($wpdb->prefix . 'facecheck_photos', array('face' => $face), array('id' => $id));
            } else {
                return 'not_face';
            }
        }

        if (empty($markers) || $reset) {
            ob_start();
                include __DIR__ . '/../templates/requests/getFaceImage.xml.php';
            $request = ob_get_clean();

            $response = Unirest::post(
                'http://www.betafaceapi.com/service.svc/GetFaceImage',
                array(
                    "Content-Type" => "application/xml"
                ),
                $request
            );

            $response = new SimpleXMLElement($response->body);
            if ($response->int_response->__toString() == '1') {
                return false;
            }

            file_put_contents(WP_CONTENT_DIR . '/photos/face_' . $id . '.jpg', base64_decode($response->face_image->__toString()));

            $faceInfo = $response->face_info;

            $allMarkers = array();
            $markers = array();
            foreach ($faceInfo->points->children() as $point) {
                $allMarkers[$point->type->__toString()] = array(
                    'x' => $point->x->__toString(),
                    'y' => $point->y->__toString()
                );
            }

            //рот
            $markers['ml'] = $allMarkers['2048'];
            $markers['mt'] = $allMarkers['4259840'];
            $markers['mr'] = $allMarkers['2304'];
            $markers['mb'] = $allMarkers['4521984'];

            //глаза
            $markers['ylo'] = $allMarkers['2555904'];
            $markers['ylt'] = $allMarkers['2686976'];
            $markers['yli'] = $allMarkers['2818048'];
            $markers['ylb'] = $allMarkers['2949120'];

            $markers['yri'] = $allMarkers['2293760'];
            $markers['yrt'] = $allMarkers['2424832'];
            $markers['yro'] = $allMarkers['2031616'];
            $markers['yrb'] = $allMarkers['2162688'];

            //брови
            $markers['blo'] = $allMarkers['3866624'];
            $markers['blm'] = array(
                'x' => ($allMarkers['3735552']['x'] + $allMarkers['3997696']['x']) / 2,
                'y' => ($allMarkers['3735552']['y'] + $allMarkers['3997696']['y']) / 2
            );
            $markers['bli'] = $allMarkers['3604480'];

            $markers['bri'] = $allMarkers['3080192'];
            $markers['brm'] = array(
                'x' => ($allMarkers['3211264']['x'] + $allMarkers['3473408']['x']) / 2,
                'y' => ($allMarkers['3211264']['y'] + $allMarkers['3473408']['y']) / 2
            );
            $markers['bro'] = $allMarkers['3342336'];

            $wpdb->update($wpdb->prefix . 'facecheck_photos', array('markers' => json_encode($markers)), array('id' => $id));
        }

        return $markers;
    }

    public static function saveMarkers($id, $markers) {
        global $wpdb;

        if (is_array($markers)) {
            $markers = json_encode($markers);
        }

        $wpdb->update($wpdb->prefix . 'facecheck_photos', array('markers' => $markers), array('id' => $id));
    }

    public static function getPhoto($id) {
        global $wpdb;

        return $wpdb->get_row("SELECT * FROM `{$wpdb->prefix}facecheck_photos` WHERE `id`='$id'");
    }

    public static function getResult($id) {
        global $wpdb;

        $photo = self::getPhoto($id);

        if (!$photo->user_id && isset($_SESSION['facecheck_user'])) {
            $wpdb->update($wpdb->prefix . 'facecheck_photos', array('user_id' => $_SESSION['facecheck_user']->id), array('id' => $photo->id));
        }

        if (!empty($photo->markers)) {
            $markers = json_decode($photo->markers, true);
            $faceparts = array();

            $mMiddleLine = ($markers['ml']['y'] + $markers['mt']['y'] + $markers['mb']['y'] + $markers['mr']['y']) / 4; //средняя линия рта

            //брови
            $bMiddleLine = ($markers['blo']['y'] + $markers['blm']['y'] + $markers['bli']['y'] + $markers['bro']['y'] + $markers['brm']['y'] + $markers['bri']['y']) / 6; // средняя линия бровей
            if (($markers['blo']['y'] >= $markers['bli']['y']) && ($markers['bri']['y'] < $markers['bro']['y'])) { // тип 1
                $faceparts[1] = $wpdb->get_var("SELECT `id` FROM `{$wpdb->prefix}facecheck_facepart_types` WHERE `id_facepart`='1' AND `name`='Тип 1'");
            } elseif (($markers['blo']['y'] < $markers['bli']['y']) && ($markers['bri']['y'] >= $markers['bro']['y'])) { // тип 2
                $faceparts[1] = $wpdb->get_var("SELECT `id` FROM `{$wpdb->prefix}facecheck_facepart_types` WHERE `id_facepart`='1' AND `name`='Тип 2'");
            } elseif (($markers['blo']['y'] >= $markers['bli']['y']) && ($markers['bri']['y'] >= $markers['bro']['y'])) { // тип 3
                $faceparts[1] = $wpdb->get_var("SELECT `id` FROM `{$wpdb->prefix}facecheck_facepart_types` WHERE `id_facepart`='1' AND `name`='Тип 3'");
            } else { // тип 4
                $faceparts[1] = $wpdb->get_var("SELECT `id` FROM `{$wpdb->prefix}facecheck_facepart_types` WHERE `id_facepart`='1' AND `name`='Тип 4'");
            }

            // глаза
            $yLHeight = $markers['ylb']['y'] - $markers['ylt']['y']; // высота левого глаза
            $yRHeight = $markers['yrb']['y'] - $markers['yrt']['y']; // высота правого глаза
            $yHeight = ($yLHeight + $yRHeight) / 2; // средняя высота глаза
            $faceHeight = $mMiddleLine - $bMiddleLine; // расстояние от средней линии рта до средней линии бровей

            if (abs($yLHeight - $yRHeight) > $yHeight * get_option('facecheck_constant_eyetoeye') / 100 ) { // разница в размере глаз больше "eyetoeye" процентов среднего глаза
                if ($yLHeight > $yRHeight) { // тип 4
                    $faceparts[2] = $wpdb->get_var("SELECT `id` FROM `{$wpdb->prefix}facecheck_facepart_types` WHERE `id_facepart`='2' AND `name`='Тип 4'");
                } else { // тип 3
                    $faceparts[2] = $wpdb->get_var("SELECT `id` FROM `{$wpdb->prefix}facecheck_facepart_types` WHERE `id_facepart`='2' AND `name`='Тип 3'");
                }
            } elseif ($yHeight > $faceHeight * get_option('facecheck_constant_eyestoface') / 100) { // тип 2
                $faceparts[2] = $wpdb->get_var("SELECT `id` FROM `{$wpdb->prefix}facecheck_facepart_types` WHERE `id_facepart`='2' AND `name`='Тип 2'");
            } else { // тип 1
                $faceparts[2] = $wpdb->get_var("SELECT `id` FROM `{$wpdb->prefix}facecheck_facepart_types` WHERE `id_facepart`='2' AND `name`='Тип 1'");
            }

            //рот
            $mLRHeight = abs($markers['ml']['y'] - $markers['mr']['y']); // расстояние между крайними точками рта
            $mBTHeight = $markers['mb']['y'] - $markers['mt']['y']; // расстояние между средними точками рта (высота рта)
            if ($mLRHeight < $mBTHeight * get_option('facecheck_constant_mlrtombt')) { // расстояние между крайними точками рта меньше 10% от высоты рта
                if (($mMiddleLine - $markers['mt']['y']) >= ($markers['mb']['y'] - $mMiddleLine)) { // тип 1
                    $faceparts[3] = $wpdb->get_var("SELECT `id` FROM `{$wpdb->prefix}facecheck_facepart_types` WHERE `id_facepart`='3' AND `name`='Тип 1'");
                } else { //тип 2
                    $faceparts[3] = $wpdb->get_var("SELECT `id` FROM `{$wpdb->prefix}facecheck_facepart_types` WHERE `id_facepart`='3' AND `name`='Тип 2'");
                }
            } else {
                if ($markers['ml']['y'] > $markers['mr']['y']) { // тип 3
                    $faceparts[3] = $wpdb->get_var("SELECT `id` FROM `{$wpdb->prefix}facecheck_facepart_types` WHERE `id_facepart`='3' AND `name`='Тип 3'");
                } else { // тип 4
                    $faceparts[3] = $wpdb->get_var("SELECT `id` FROM `{$wpdb->prefix}facecheck_facepart_types` WHERE `id_facepart`='3' AND `name`='Тип 4'");
                }
            }

            if ($photo->date == '0000-00-00 00:00:00') {
                $wpdb->update($wpdb->prefix . 'facecheck_photos', array('date' => date('Y-m-d H:i:s')), array('id' => $photo->id));
            }

            return self::getReport($faceparts);
        }
    }

    public function savePhoto($id, $name, $comment, $groups = array()) {
        global $wpdb;

        $wpdb->update($wpdb->prefix . 'facecheck_photos', array('name' => $name, 'comment' => $comment), array('id' => $id));
    }

    public function updatePhotoGroups($photo_id, $group_ids = array()) {
        global $wpdb;

        $wpdb->delete($wpdb->prefix . 'facecheck_photo_category', array('photo_id' => $photo_id));

        foreach($group_ids as $gid) {
            $wpdb->insert($wpdb->prefix . 'facecheck_photo_category', array('photo_id' => $photo_id, 'category_id' => $gid));
        }
    }

    public function getPhotoGroups($photo_id) {
        global $wpdb;

        $rows = $wpdb->get_results("
            SELECT `uc`.*
            FROM `{$wpdb->prefix}facecheck_photo_category` AS `pc`
            LEFT JOIN `{$wpdb->prefix}facecheck_user_categories` AS `uc` ON `uc`.`id`=`pc`.`category_id`
            WHERE `pc`.`photo_id`='{$photo_id}'
            GROUP BY `uc`.`id`
        ", ARRAY_A);

        $result = array();
        if($rows) {
            foreach($rows as $row) {
                $result[$row['id']] = $row;
            }
        }

        return $result;
    }
}