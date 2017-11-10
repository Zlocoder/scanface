<?php
/*
Plugin Name: Facecheck
Description: Make Psychoreports by uploaded photos. Manage reports.
Version: 1.0
Author: Zlocoder
*/

include __DIR__ . '/classes/facecheck-user.php';
include __DIR__ . '/classes/facecheck.php';

register_activation_hook(__FILE__, 'facecheck_activate');
register_deactivation_hook(__FILE__, 'facecheck_deactivate');

function facecheck_activate() {
    Facecheck::install();
}

function facecheck_deactivate() {
    Facecheck::uninstall();
}

add_action('init', 'facecheck_init');

function facecheck_init() {
    add_rewrite_tag('%facecheck_photo_id%', '([0-9]+)');
    add_rewrite_tag('%filter%', '(.*)');
    add_rewrite_tag('%reverse%', '(.*)');
    add_rewrite_tag('%name%', '(.*)');
    add_rewrite_tag('%category%', '([0-9]+)');
    add_rewrite_tag('%profession%', '([0-9]+)');

    add_option('facecheck_constant_eyetoeye', '10');
    add_option('facecheck_constant_eyestoface', '15');
    add_option('facecheck_constant_mlrtombt', '20');

    Facecheck::init();
}

//admin actions
add_action('admin_init', 'facecheck_admin_init');
add_action('admin_menu', 'facecheck_admin_menu');
add_action('admin_head-facecheck/admin/specifications.php', 'facecheck_admin_head');
add_action('admin_head-facecheck/admin/profcats.php', 'facecheck_admin_head');
add_action('admin_head-facecheck/admin/professions.php', 'facecheck_admin_head');
add_action('admin_head-facecheck/admin/sections.php', 'facecheck_admin_head');
add_action('admin_head-facecheck/admin/reports.php', 'facecheck_admin_head');


function facecheck_admin_init() {
    register_setting('facecheck_constants', 'facecheck_constant_eyetoeye');
    register_setting('facecheck_constants', 'facecheck_constant_eyestoface');
    register_setting('facecheck_constants', 'facecheck_constant_mlrtombt');
}

function facecheck_admin_menu() {
    global $menu;
    add_menu_page('Scanface', 'Scanface', 'manage_options', 'facecheck/admin/facecheck.php', '', '', 7);
    add_submenu_page('facecheck/admin/facecheck.php', 'Характеристики', 'Характеристики', 'manage_options', 'facecheck/admin/specifications.php');
    add_submenu_page('facecheck/admin/facecheck.php', 'Группы профессий', 'Группы профессий', 'manage_options', 'facecheck/admin/profcats.php');
    add_submenu_page('facecheck/admin/facecheck.php', 'Профессии', 'Профессии', 'manage_options', 'facecheck/admin/professions.php');
    add_submenu_page('facecheck/admin/facecheck.php', 'Sections', 'Sections', 'manage_options', 'facecheck/admin/sections.php');
    add_submenu_page('facecheck/admin/facecheck.php', 'Reports', 'Reports', 'manage_options', 'facecheck/admin/reports.php');
    add_submenu_page('facecheck/admin/facecheck.php', 'Пользователи', 'Пользователи', 'manage_options', 'facecheck/admin/users.php');
    //add_submenu_page('facecheck/admin/reports.php', 'Faceparts', 'Faceparts', 'manage_options', 'facecheck/admin/faceparts.php');
}

function facecheck_admin_head() {
    echo '<link rel="stylesheet" href="'. plugins_url('facecheck/admin/style.css') . '" />';
    echo '<script type="text/javascript" src="' . includes_url('js/jquery/ui/jquery.ui.core.min.js') . '"></script>';
    echo '<script type="text/javascript" src="' . includes_url('js/jquery/ui/jquery.ui.widget.min.js') . '"></script>';
    echo '<script type="text/javascript" src="' . includes_url('js/jquery/ui/jquery.ui.draggable.min.js') . '"></script>';
    echo '<script type="text/javascript" src="' . plugins_url('facecheck/admin/jquery.fileupload.js') . '"></script>';
}

//ajax request actions
add_action('wp_ajax_facecheck_upload_photo', 'facecheck_upload_photo');
add_action('wp_ajax_facecheck_process_photo', 'facecheck_process_photo');
//add_action('wp_ajax_facecheck_reset_markers', 'facecheck_reset_markers');
//add_action('wp_ajax_facecheck_save_markers', 'facecheck_save_markers');
add_action('wp_ajax_facecheck_delete_photo', 'facecheck_delete_photo');
add_action('wp_ajax_facecheck_save_report', 'facecheck_save_report');
add_action('wp_ajax_facecheck_report_letter', 'facecheck_report_letter');
add_action('wp_ajax_facecheck_upload_sicon', 'facecheck_upload_sicon');
add_action('wp_ajax_facecheck_delete_profession', 'facecheck_delete_profession');

function facecheck_upload_photo() {
    if (empty($_FILES['photo']) || $_FILES['photo']['error'] || $_FILES['photo']['size'] == 0) {
        exit;
    }

    $id = Facecheck::addPhoto($_FILES['photo']['tmp_name']);

    echo $id;
    exit;
}

function facecheck_process_photo() {
    if (empty($_POST['id'])) {
        exit;
    }

    $result = Facecheck::getMarkers($_POST['id']);
    if ($result == 'not_face') {
        echo 'not_face';
    } elseif ($result) {
        echo '1';
    }

    exit;
}

function facecheck_delete_photo() {
    if (empty($_REQUEST['id'])) {
        exit;
    }

    Facecheck::deletePhoto($_REQUEST['id']);

    if (isset($_REQUEST['redirect'])) {
        wp_redirect($_REQUEST['redirect']);
    }

    exit;
}

function facecheck_save_report() {
    global $wpdb;

    if (!isset($_POST['id']) || empty($_POST['id'])) {
        exit;
    }

    if (isset($_POST['groups']) && count($_POST['groups'])) {
        foreach($_POST['groups'] as $index => $group) {
            if (strpos($group, 'group_') === 0) {
                $group = str_replace('group_', '', $group);
                $_POST['groups'][$index] = $_SESSION['facecheck_user']->addGroup($group);
            }
        }

        Facecheck::updatePhotoGroups($_POST['id'], $_POST['groups']);
    } else {
        Facecheck::updatePhotoGroups($_POST['id'], array());
    }

    Facecheck::savePhoto($_POST['id'], $_POST['name'], $_POST['comment']);

    echo 'success'; die();
}

function facecheck_report_letter() {
    global $wpdb;

    if (isset($_POST['email']) && count($_POST['email'])) {
        $report = facecheck_get_result($_POST['id']);
        $photo = Facecheck::getPhoto($_POST['id']);

        include_once __DIR__ . '/classes/facecheck-report-pdf.php';

        $pdf = new FacecheckReportPDF($_POST['id']);
        $pdf->Output(WP_CONTENT_DIR  . '/uploads/report.pdf', 'F');

        ob_start();
        $rows = $wpdb->get_results("SELECT * FROM `{$wpdb->prefix}facecheck_report_sections`", ARRAY_A);
        $sections = array();
        foreach ($rows as $row) {
            $sections[$row['id']] = $row;
        }

        $profession = $_POST['profession'];
        $specifications = Facecheck::getSpecifications();

        include __DIR__ . '/templates/report-mail.php';
        $content = ob_get_clean();

        add_filter( 'wp_mail_content_type', 'facecheck_html_email_type' );
        foreach ($_POST['email'] as  $email) {
            wp_mail($email, 'Scanface анализ', $content, 'From: "Scanface" <report@scanface.com.ua>', array(WP_CONTENT_DIR ."/uploads/report.pdf"));
            $wpdb->replace("{$wpdb->prefix}facecheck_users_emails", array('id_user' => $_SESSION['facecheck_user']->id, 'email' => $email));
        }
        remove_filter( 'wp_mail_content_type', 'facecheck_html_email_type' );

        echo 'success';
    }

    die();
}

function facecheck_upload_sicon() {
    if(empty($_FILES['sicon']) || $_FILES['sicon']['error'] || $_FILES['sicon']['size'] == 0) {
        exit;
    }

    move_uploaded_file($_FILES['sicon']['tmp_name'], WP_CONTENT_DIR . '/plugins/facecheck/sicons/temp.jpg');
    echo plugins_url('facecheck/sicons/temp.jpg');
    exit;
}

function facecheck_delete_profession()
{
    global $wpdb;

    if (empty($_POST['profession'])) {
        exit;
    }

    $profession = $wpdb->get_results("
        SELECT * FROM `wp_facecheck_professions`
        WHERE `id` = {$_POST['profession']}
    ", ARRAY_A);

    if (empty($profession) || $profession['id_user'] != $_SESSION['facecheck_user']->id) {
        exit;
    }

    $wpdb->query("DELETE FROM `wp_facecheck_professions` WHERE `id` = {$_POST['profession']}");
    $wpdb->query("DELETE FROM `wp_facecheck_professions_specifications` WHERE `id_profession` = {$_POST['profession']}");

    echo 'success';
    exit;
}

//forms actions
//add_action('admin_post_facecheck_user_registration', 'facecheck_user_registration');
//add_action('admin_post_facecheck_user_login', 'facecheck_user_login');
//add_action('admin_post_facecheck_oauth_facebook', 'facecheck_oauth_facebook');
//add_action('admin_post_facecheck_oauth_linkedin', 'facecheck_oauth_linkedin');
//add_action('admin_post_facecheck_user_logout', 'facecheck_user_logout');
//add_action('admin_post_facecheck_user_password', 'facecheck_user_password');
//add_action('admin_post_facecheck_save_profile', 'facecheck_save_profile');
//add_action('admin_post_facecheck_save_markers', 'facecheck_save_markers');
//add_action('admin_post_facecheck_delete_photo', 'facecheck_delete_photo');
add_action('admin_post_facecheck_save_section', 'facecheck_save_section');
add_action('admin_post_facecheck_save_profession', 'facecheck_save_profession');
add_action('admin_post_facecheck_save_profession_category', 'facecheck_save_profession_category');
add_action('admin_post_facecheck_save_specification', 'facecheck_save_specification');
//add_action('admin_post_facecheck_save_custom_specifications', 'facecheck_save_custom_specifications');

function facecheck_user_registration() {
    global $wpdb;
    $errors = array();

    if (empty($_POST['name'])) {
        $errors['name'] = 'Введите имя и фамилию';
    }

    if (empty($_POST['email'])) {
        $errors['email'] = 'Введите email';
    } elseif (!preg_match('/[0-9a-zA-Z][-0-9a-zA-Z.+_]+@[0-9a-zA-Z][-0-9a-zA-Z.+_]+\.[a-zA-Z]{2,4}/', $_POST['email'])) {
        $errors['email'] = 'Некорректный email';
    } elseif (Facecheckuser::getByEmail($_POST['email'])) {
        $errors['email'] = 'Пользователь с таким email уже зарегистрирован';
    }

    if (empty($_POST['password'])) {
        $errors['password'] = 'Введите пароль';
    }

    if ($errors) {
        return array(
            'errors' => $errors,
            'name' => $_POST['name'],
            'email' => $_POST['email'],
            'day' => $_POST['day'],
            'month' => $_POST['month'],
            'year' => $_POST['year']
        );
    } else {
        $user = new FacecheckUser();
        $user->name = $_POST['name'];
        $user->email = $_POST['email'];
        $user->password = md5($_POST['password']);
        $user->date_birth = $_POST['year'] . '-' . $_POST['month'] . '-' . $_POST['day'];
        $user->save();

        $wpdb->insert("{$wpdb->prefix}facecheck_users_emails", array('id_user' => $user->id, 'email' => $user->email));

        $groups = array(
            array('user_id' => $user->id, 'name' => 'Работа', 'date' => date('Y-m-d H:i:').'00'),
            array('user_id' => $user->id, 'name' => 'Сотрудники', 'date' => date('Y-m-d H:i:').'01'),
            array('user_id' => $user->id, 'name' => 'Личное', 'date' => date('Y-m-d H:i:').'02')
        );

        $user->groups($groups);

        $_SESSION['facecheck_user'] = $user;
        wp_redirect($_POST['success_page']);
    }
}

function facecheck_user_login() {
    $errors = array();

    if (empty($_POST['email'])) {
        $errors['email'] = 'Введите email';
    } elseif (!preg_match('/[0-9a-zA-Z][-0-9a-zA-Z.+_]+@[0-9a-zA-Z][-0-9a-zA-Z.+_]+\.[a-zA-Z]{2,4}/', $_POST['email'])) {
        $errors['email'] = 'Некорректный email';
    } elseif (!$user = FacecheckUser::getByEmail($_POST['email'])) {
        $errors['email'] = 'Пользователь с таким email не найден';
    }

    if (empty($_POST['password'])) {
        $errors['password'] = 'Введите пароль';
    } elseif ($user && md5($_POST['password']) != $user->password) {
        $errors['password'] = 'Неверный пароль';
    }

    if ($errors) {
        return array(
            'errors' => $errors,
            'email' => $_POST['email']
        );
    } else {
        $_SESSION['facecheck_user'] = $user;
        wp_redirect($_POST['success_page']);
    }
}

function facecheck_oauth_facebook() {
    if (isset($_GET['code'])) {
        $url = 'https://graph.facebook.com/oauth/access_token?';
        $url .= 'client_id=759154490783588';
        $url .= '&redirect_uri=' . urlencode(get_permalink(get_page_by_title('Вход')) . '?action=facecheck_oauth_facebook');
        $url .= '&client_secret=49b42ef3f1d3f06a19e76d4b491e84be';
        $url .= '&code='.$_GET['code'];

        parse_str(file_get_contents($url), $response);
        if (!isset($response['access_token'])) {
            wp_redirect(get_permalink(get_page_by_title('Вход')));
        }

        $token = $response['access_token'];
        $response = json_decode(file_get_contents('https://graph.facebook.com/me?access_token=' . $token), true);

        if (!$response || isset($response['error']) || !$response['email']) {
            wp_redirect(get_permalink(get_page_by_title('Вход')));
        }

        $user = FacecheckUser::getByEmail($response['email']);

        if (!isset($user)) {
            $user = new FacecheckUser();
            $user->email = $response['email'];
            $user->name = $response['name'];
            $user->date_birth = substr($response['birthday'], 6, 4) . '-' . substr($response['birthday'], 0, 2) . '-' . substr($response['birthday'], 3, 2);
            $user->save();
        }

        $_SESSION['facecheck_user'] = $user;

        if (isset($_SESSION['redirect_result_id'])) {
            wp_redirect(add_query_arg('facecheck_photo_id', $_SESSION['redirect_result_id'], get_permalink(get_page_by_title('Result'))));
        } else {
            wp_redirect(get_permalink(get_page_by_title('Upload')));
        }
    } else {
        wp_redirect(get_permalink(get_page_by_title('Вход')));
    }
}

function facecheck_oauth_linkedin() {
    if (isset($_GET['code'])) {
        $url = 'https://www.linkedin.com/uas/oauth2/accessToken?grant_type=authorization_code';
        $url .= '&code='.$_GET['code'];
        $url .= '&redirect_uri=' . urlencode(get_permalink(get_page_by_title('Вход')) . '?action=facecheck_oauth_linkedin');
        $url .= '&client_id=75ffuo7grpudeh';
        $url .= '&client_secret=Q6wgsPax2BoCBEXB';

        $response = json_decode(file_get_contents($url), true);

        if (!isset($response['access_token'])) {
            wp_redirect(get_permalink(get_page_by_title('Вход')));
        }

        $token = $response['access_token'];
        $response = json_decode(file_get_contents('https://api.linkedin.com/v1/people/~:(first-name,last-name,email-address,date-of-birth)?format=json&oauth2_access_token=' . $token), true);

        if (!$response || isset($response['error']) || !$response['emailAddress']) {
            wp_redirect(get_permalink(get_page_by_title('Вход')));
        }

        $user = FacecheckUser::getByEmail($response['emailAddress']);

        if (!isset($user)) {
            $user = new FacecheckUser();
            $user->email = $response['emailAddress'];
            $user->name = $response['firstName'] . ($response['firstName'] ? ' ' . $response['lastName'] : $response['lastName']);
            if (isset($response['dateOfBirth'])) {
                $user->date_birth = $response['dateOfBirth']['year'] . '-' . $response['dateOfBirth']['month'] . '-' . $response['dateOfBirth']['day'];
            }
            $user->save();
        }

        $_SESSION['facecheck_user'] = $user;
        if (isset($_SESSION['redirect_result_id'])) {
            wp_redirect(add_query_arg('facecheck_photo_id', $_SESSION['redirect_result_id'], get_permalink(get_page_by_title('Result'))));
        } else {
            wp_redirect(get_permalink(get_page_by_title('Upload')));
        }
    } else {
        wp_redirect(get_permalink(get_page_by_title('Вход')));
    }
}

function facecheck_user_logout() {
    unset($_SESSION['facecheck_user']);
    wp_redirect($_GET['redirect']);
}

function facecheck_user_password() {
    $errors = array();

    if (empty($_POST['email'])) {
        $errors['email'] = 'Введите email';
    } elseif (!preg_match('/[0-9a-zA-Z][-0-9a-zA-Z.+_]+@[0-9a-zA-Z][-0-9a-zA-Z.+_]+\.[a-zA-Z]{2,4}/', $_POST['email'])) {
        $errors['email'] = 'Некорректный email';
    } elseif (!$user = FacecheckUser::getByEmail($_POST['email'])) {
        $errors['email'] = 'Пользователь с таким email не найден';
    }

    if ($errors) {
        return array(
            'errors' => $errors,
            'email' => $_POST['email']
        );
    } else {
        $newpass = substr(uniqid(), 0, 6);

        $user->password = md5($newpass);
        $user->save();

        ob_start();
        include __DIR__ . '/templates/password-mail.php';
        $content = ob_get_clean();

        add_filter( 'wp_mail_content_type', 'facecheck_html_email_type' );
        wp_mail($_POST['email'], 'Новый пароль', $content, 'From: "Scanface" <report@scanface.com.ua>');
        remove_filter( 'wp_mail_content_type', 'facecheck_html_email_type' );

        $_SESSION['new_password'] = true;
        wp_redirect($_POST['success_page']);
    }
}

function facecheck_save_profile() {
    $errors = array();

    $user = $_SESSION['facecheck_user'];

    if (empty($_POST['name'])) {
        $errors['name'] = 'Введите имя';
    }

    if (!empty($_POST['old_password']) && md5($_POST['old_password']) != $user->password) {
        $errors['old_password'] = 'Неверный старый пароль';
    }

    if (!empty($_POST['old_password']) && empty($errors['old_password']) && empty($_POST['new_password'])) {
        $errors['new_password'] = 'Введите новый пароль';
    }

    if (empty($errors)) {
        $_SESSION['facecheck_user']->name = $_POST['name'];
        $_SESSION['facecheck_user']->date_birth = $_POST['year'] . '-' . $_POST['month'] . '-' . $_POST['day'];

        if ($_POST['old_password']) {
            $_SESSION['facecheck_user']->password = md5($_POST['new_password']);
        }

        $_SESSION['facecheck_user']->save();
    } else {
       return $errors;
    }
}

/*
function facecheck_reset_markers() {
    if (empty($_POST['id'])) {
        exit;
    }

    $markers = Facecheck::getMarkers($_POST['id'], true);

    echo json_encode($markers);
    exit;
}
*/

function facecheck_save_markers() {
    if (empty($_POST['id']) || empty($_POST['marker'])) {
        exit;
    }

    Facecheck::saveMarkers($_POST['id'], json_encode($_POST['marker']));

    if ($_POST['redirectUrl']) {
        wp_redirect($_POST['redirectUrl']);
    }

    exit;
}

function facecheck_save_section() {
    global $wpdb;

    if($_POST['id']) {
        $wpdb->update($wpdb->prefix . 'facecheck_report_sections', array('name' => $_POST['name']), array('id' => $_POST['id']));
        $id = $_POST['id'];
    } else {
        $position = $wpdb->get_var("SELECT MAX(`position`) FROM `{$wpdb->prefix}facecheck_report_sections`") + 1;
        $wpdb->insert($wpdb->prefix . 'facecheck_report_sections', array('name' => $_POST['name'], 'position' => $position));
        $id = $wpdb->insert_id;
    }

    if(file_exists(WP_CONTENT_DIR . '/plugins/facecheck/sicons/temp.jpg')) {
        rename(WP_CONTENT_DIR . '/plugins/facecheck/sicons/temp.jpg', WP_CONTENT_DIR . '/plugins/facecheck/sicons/' . $id . '.jpg');
    }

    wp_redirect(admin_url() . 'admin.php?page=facecheck/admin/sections.php');
}

function facecheck_save_profession_category() {
    global $wpdb;

    if($_POST['id']) {
        $wpdb->update($wpdb->prefix . 'facecheck_profession_categories', array('name' => $_POST['name']), array('id' => $_POST['id']));
        $id = $_POST['id'];
    } else {
        $wpdb->insert($wpdb->prefix . 'facecheck_profession_categories', array('name' => $_POST['name']));
        $id = $wpdb->insert_id;
    }

    wp_redirect(admin_url() . 'admin.php?page=facecheck/admin/profcats.php');
}

function facecheck_save_profession() {
    global $wpdb;

    if($_POST['id']) {
        $wpdb->update($wpdb->prefix . 'facecheck_professions', array('name' => $_POST['name'], 'id_category' => $_POST['category']), array('id' => $_POST['id']));
        $id = $_POST['id'];
    } else {
        $wpdb->insert($wpdb->prefix . 'facecheck_professions', array('name' => $_POST['name']));
        $id = $wpdb->insert_id;
    }

    foreach ($_POST['specification'] as $key => $spec) {
        $data = array(
            'id_profession' => $id,
            'id_specification' => $key,
            'left_value' => $spec['left'],
            'right_value' => $spec['right'],
            'description' => $spec['descr']
        );
        $wpdb->replace($wpdb->prefix.'facecheck_professions_specifications', $data);
    }

    wp_redirect(admin_url() . 'admin.php?page=facecheck/admin/professions.php');
}

function facecheck_save_specification() {
    global $wpdb;

    $data = array(
        'name' => $_POST['name'],
        'left_value' => $_POST['left-value'],
        'right_value' => $_POST['right-value'],
        'left_text' => $_POST['left-text'],
        'right_text' => $_POST['right-text'],
        'left_title' => $_POST['left-title'],
        'right_title' => $_POST['right-title']
    );

    if ($_POST['id']) {
        $wpdb->update($wpdb->prefix . 'facecheck_specifications', $data, array('id' => $_POST['id']));
    } else {
        $wpdb->insert($wpdb->prefix . 'facecheck_specifications', $data);
        $id = $wpdb->insert_id;
    }

    wp_redirect(admin_url() . 'admin.php?page=facecheck/admin/specifications.php');
}

function facecheck_save_custom_specifications() {
    global $wpdb;

    if ($_SESSION['facecheck_user']) {
        $errors = array();
        if (empty($_POST['name'])) {
            $errors['name'] = 'Введите название профессии';
        } else {
            $exists = $wpdb->get_row("
                SELECT *
                FROM `{$wpdb->prefix}facecheck_professions`
                WHERE `id_user` = {$_SESSION['facecheck_user']->id} AND `name` = '{$_POST['name']}'
            ", ARRAY_A);

            if (!empty($exists)) {
                $errors['name'] = 'Вы уже создали профессию с таким названием';
            }
        }

        if (empty($errors)) {
            if (empty($_POST['id_profession'])) {
                $wpdb->insert($wpdb->prefix . 'facecheck_professions', array('name' => $_POST['name'], 'id_category' => $_POST['id_category'], 'id_user' => $_SESSION['facecheck_user']->id));
                $_POST['id_profession'] = $wpdb->insert_id;

                foreach ($_POST['specval'] as $id => $specval) {
                    $wpdb->insert(
                        $wpdb->prefix . 'facecheck_professions_specifications',
                        array(
                            'id_profession' => $_POST['id_profession'],
                            'id_specification' => $id,
                            'left_value' => $specval['left'],
                            'right_value' => $specval['right'],
                            'description' => ''
                        )
                    );
                }
            } else {
                $wpdb->update($wpdb->prefix . 'facecheck_professions', array('name' => $_POST['name']), array('id' => $_POST['id_profession']));

                foreach ($_POST['specval'] as $id => $specval) {
                    $wpdb->update(
                        $wpdb->prefix . 'facecheck_professions_specifications',
                        array(
                            'left_value' => $specval['left'],
                            'right_value' => $specval['right'],
                        ),
                        array(
                            'id_profession' => $_POST['id_profession'],
                            'id_specification' => $id
                        )
                    );
                }
            }
            wp_redirect(get_permalink(get_page_by_title('Professions')));
        } else {
            return array(
                'errors' => $errors,
                'specval' => $_POST['specval']
            );
        }
    }

    exit;
}

//служебные функции
function facecheck_get_photo($id) {
    if (empty($id)) {
        return null;
    }

    return Facecheck::getPhoto($id);
}

function facecheck_get_result($id) {
    return Facecheck::getResult($id);
}

function facecheck_html_email_type() {
    return 'text/html';
}
?>