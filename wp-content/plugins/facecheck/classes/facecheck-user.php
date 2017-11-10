<?php

class FacecheckUser {
    public $id;
    public $email;
    public $password;
    public $name;
    public $date_birth;
    public $date;
    public $groups;
    public $use_personal_specifications;

    public static function getById($id) {
        global $wpdb;

        $row = $wpdb->get_row("SELECT * FROM `{$wpdb->prefix}facecheck_users` WHERE `id`='{$id}'", ARRAY_A);
        if ($row) {
            $user = new self();
            $user->id = $row['id'];
            $user->email = $row['email'];
            $user->password = $row['password'];
            $user->name = $row['name'];
            $user->date_birth = $row['date_birth'];
            $user->date = $row['date'];
            $user->use_personal_specifications = $row['use_personal_specifications'];
            return $user;
        }

        return null;
    }

    public static function getByEmail($email) {
        global $wpdb;

        $row = $wpdb->get_row("SELECT * FROM `{$wpdb->prefix}facecheck_users` WHERE `email`='{$email}'", ARRAY_A);
        if ($row) {
            $user = new self();
            $user->id = $row['id'];
            $user->email = $row['email'];
            $user->password = $row['password'];
            $user->name = $row['name'];
            $user->date_birth = $row['date_birth'];
            $user->date = $row['date'];
            $user->use_personal_specifications = $row['use_personal_specifications'];
            return $user;
        }

        return null;
    }

    public static function getByFacebookId($id) {
        global $wpdb;
        $row = $wpdb->get_row("SELECT * FROM `{$wpdb->prefix}facecheck_users` WHERE `facebook_id`='{$id}'", ARRAY_A);
        if ($row) {
            $user = new self();
            $user->id = $row['id'];
            $user->email = $row['email'];
            $user->password = $row['password'];
            $user->name = $row['name'];
            $user->date_birth = $row['date_birth'];
            $user->date = $row['date'];
            $user->use_personal_specifications = $row['use_personal_specifications'];
            return $user;
        }

        return null;
    }

    public function save() {
        global $wpdb;

        if ($this->id) {
            $wpdb->update("{$wpdb->prefix}facecheck_users", array(
                'email' => $this->email,
                'password' => $this->password,
                'name' => $this->name,
                'date_birth' => $this->date_birth,
                'use_personal_specifications' => ($this->use_personal_specifications ? 1 : 0)
            ), array('id' => $this->id));
        } else {
            if (empty($this->date)) {
                $this->date = date('Y-m-d');
            }
            $wpdb->insert("{$wpdb->prefix}facecheck_users", array(
                'email' => $this->email,
                'password' => $this->password,
                'name' => $this->name,
                'date_birth' => $this->date_birth,
                'date' => $this->date,
                'use_personal_specifications' => ($this->use_personal_specifications ? 1 : 0)
            ));
            $this->id = $wpdb->insert_id;
        }
    }

    public function addGroup($name) {
        global $wpdb;

        $group = array(
            'user_id' => $this->id,
            'name' => $name,
            'date' => date('Y-m-d H:i:s')
        );

        $wpdb->insert($wpdb->prefix . 'facecheck_user_categories', $group);
        return $wpdb->insert_id;
    }

    public function groups($groups = null) {
        global $wpdb;

        if ($groups) {
            foreach ($groups as $group) {
                $wpdb->replace($wpdb->prefix . 'facecheck_user_categories', $group);
            }
        } else {
            return $wpdb->get_results("SELECT * FROM `{$wpdb->prefix}facecheck_user_categories` WHERE `user_id`='{$_SESSION['facecheck_user']->id}' ORDER BY `date` ASC", ARRAY_A);
        }
    }
}
