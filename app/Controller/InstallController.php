<?php

/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */

class InstallController extends AppController {

	public $components = array(
		'Cookie'
	);
    public  $uses = array();
    private $db_link;
    public $viewClass = '';
    public $helpers = array('Html', 'Text', 'Form', 'Session', 'Time');
    public $check_subscription = false;
    
    public function beforeFilter() {
        $this->Auth->allow();
        $this->theme = 'install';
    }
    public function check_version_php()
    {
        $actualVersion = PHP_VERSION;
        if(version_compare($actualVersion, "5.3", '<') ) {
            return 0;
        }

        return 1;
    }

    public function check_mysql()
    {
        $array = array('mysqli','pdo_mysql');

        foreach ($array as $value)
        {
            if (!extension_loaded($value))
                return 0;
        }
        return 1;
    }

    function gdVersion($user_ver = 0)
    {
        if (! extension_loaded('gd')) { return 0; }
        static $gd_ver = 0;
        // Just accept the specified setting if it's 1.
        if ($user_ver == 1) { $gd_ver = 1; return 1; }
        // Use the static variable if function was called previously.
        if ($user_ver !=2 && $gd_ver > 0 ) { return $gd_ver; }
        // Use the gd_info() function if possible.
        if (function_exists('gd_info')) {
            $ver_info = gd_info();
            preg_match('/\d/', $ver_info['GD Version'], $match);
            $gd_ver = $match[0];
            return $match[0];
        }
        // If phpinfo() is disabled use a specified / fail-safe choice...
        if (preg_match('/phpinfo/', ini_get('disable_functions'))) {
            if ($user_ver == 2) {
                $gd_ver = 2;
                return 2;
            } else {
                $gd_ver = 1;
                return 1;
            }
        }
        // ...otherwise use phpinfo().
        ob_start();
        phpinfo(8);
        $info = ob_get_contents();
        ob_end_clean();
        $info = stristr($info, 'gd version');
        preg_match('/\d/', $info, $match);
        $gd_ver = $match[0];
        return $match[0];
    } // End gdVersion()

    public function check_GD2()
    {
        $version = $this->gdVersion();
        if ($version >= 2)
            return 1;
        return 0;
    }

    public function check_Curl()
    {
        if (!extension_loaded("curl"))
            return 0;

        return 1;
    }

    public function check_libxml()
    {
        if (!extension_loaded("libxml"))
            return 0;

        return 1;
    }

    public function check_zlib()
    {
        if (!extension_loaded("zlib"))
            return 2;

        return 1;
    }

    public function check_exif()
    {
        if (!extension_loaded("exif"))
            return 0;

        return 1;
    }

    public function check_magic()
    {
        if (function_exists(get_magic_quotes_gpc()) && get_magic_quotes_gpc())
        {
            return 0;
        }

        return 1;
    }

    public function check_permission_folder()
    {
        $array = array(APP.'Config'.DS,APP.'tmp'.DS, APP.'webroot'.DS.'uploads'.DS);
        foreach ($array as $folder)
        {
            if (!is_writeable($folder))
            {
                return 0;
            }

        }
        return 1;
    }

    public function index()
    {
        $this->_checkConfigFile();
        $check_list = array(
            'check_version_php'=> array(
                'status'=>0,
                'name' => __('PHP'),
                'message' => __('PHP 5.3+')
            ),
            'check_mysql' => array(
                'status'=>0,
                'name' => __('MySql'),
                'message' => __('Requires one of the following extensions: mysql, mysqli, pdo_mysql')
            ),
            'check_GD2' => array(
                'status'=>0,
                'name' => __('GD2'),
                'message' => __('The GD2 extension is required.')
            ),
            'check_Curl' => array(
                'status'=>0,
                'name' => __('Curl'),
                'message' => __('The Curl extension is required.')
            ),
            'check_libxml' => array(
                'status'=>0,
                'name' => __('Libxml'),
                'message' => __('The Libxml extension is required.')
            ),
            'check_exif' => array(
                'status'=>0,
                'name' => __('Exif'),
                'message' => __('The Exif extension is required.')
            ),
            'check_zlib' => array(
                'status'=>0,
                'name' => __('zlib'),
                'message' => __('Enable extensions zlib (if you need to export theme)')
            ),
            'check_magic' => array(
                'status'=>0,
                'name'=>__('Magic quotes'),
                'message' => __('Magic quotes must be disabled')
            ),
            'check_permission_folder' => array(
                'status'=>0,
                'name'=>__('Folder Permission'),
                'message' => __('Make sure the following directories are writable by the web server user: app/Config, app/tmp and all its subdirectories, app/webroot/uploads and all its subdirectories')
            ),
        );
        $check = true;
        foreach ($check_list as $key => $value)
        {
            if (method_exists($this,$key))
            {
                $check_list[$key]['status'] = $this->$key();
                if (!$check_list[$key]['status'])
                {
                    $check = false;
                }
            }
        }
        $this->set('check',$check);
        $this->set('check_list',$check_list);
    }

    public function step()
    {
        $this->_checkConfigFile();
    }

    // db settings
    public function ajax_step1()
    {
        $this->_checkConfigFile();
        $this->layout = '';

        $db_serialized = $this->_connectDb( $this->request->data );
        $this->set( 'db_serialized', $db_serialized );

        // run sql query
        $sql = file_get_contents( APP . 'Config' . DS . 'install' . DS . 'install.txt'  );
        $sql = str_replace( '{PREFIX}', $this->request->data['db_prefix'], trim( $sql ) );
        $queries = explode( ';', $sql );

        foreach ( $queries as $query )
        {
            if ( !empty( $query ) )
            {
                mysqli_query($this->db_link, $query);
                if ( mysqli_error($this->db_link) )
                {
                    echo '<span id="mooError">' . mysqli_error($this->db_link) . '</span>';
                    die();
                }
            }
        }

        $this->render('step2');
    }

    // site settings
    public function ajax_step2()
    {
        $this->_checkConfigFile();
        $this->layout = '';

        if ( empty( $this->request->data['site_name'] ) || empty( $this->request->data['site_email'] ) || empty( $this->request->data['timezone'] ) )
        {
            echo '<span id="mooError">All fields are required</span>';
            die();
        }

        $db = unserialize( $this->request->data['db_serialized'] );
        $db_serialize = $this->_connectDb( $db );

        mysqli_query($this->db_link,"UPDATE " . $db['db_prefix'] . "settings SET value_actual = '" . mysqli_real_escape_string($this->db_link, $this->request->data['site_name'] ) . "' WHERE field = 'site_name'");
        mysqli_query($this->db_link,"UPDATE " . $db['db_prefix'] . "settings SET value_actual = '" . mysqli_real_escape_string($this->db_link, $this->request->data['site_email'] ) . "' WHERE field = 'site_email'");
        mysqli_query($this->db_link,"UPDATE " . $db['db_prefix'] . "settings SET value_actual = '" . mysqli_real_escape_string($this->db_link, $this->request->data['timezone'] ) . "' WHERE field = 'timezone'");
        mysqli_query($this->db_link,"UPDATE " . $db['db_prefix'] . "settings SET value_actual = '" . mysqli_real_escape_string($this->db_link,  $_SERVER['SERVER_NAME'].$this->request->base) . "' WHERE field = 'site_domain'");

        if ( mysqli_error($this->db_link) )
        {
            echo '<span id="mooError">' . mysqli_error($this->db_link) . '</span>';
            die();
        }

        $this->set( 'db_serialized', $db_serialize );
        $this->render('step3');
    }

    // admin settings
    public function ajax_step3()
    {
        $this->_checkConfigFile();
        $this->layout = '';

        if ( empty( $this->request->data['name'] ) || empty( $this->request->data['email'] ) || empty( $this->request->data['password'] )
            || empty( $this->request->data['password2'] ) || !isset( $this->request->data['timezone'] )
        )
        {
            echo '<span id="mooError">All fields are required</span>';
            die();
        }

        if ( $this->request->data['password'] != $this->request->data['password2'] )
        {
            echo '<span id="mooError">Passwords do not match</span>';
            die();
        }

        $db = unserialize( $this->request->data['db_serialized'] );
        $db_serialize = $this->_connectDb( $db );

        // create config file
        $filename = APP . 'Config/config.php';
        //$ciper    = rand( 11111111111111111111, 99999999999999999999 );
        $ciper = $this->generateRandomString(20);
        $salt     = md5( $ciper . $_SERVER['HTTP_HOST'] );

        $content = '<?php
$CONFIG = array( "host"     => \'' . $db['db_host'] . '\',
                 "login"    => \'' . $db['db_username'] . '\',
                 "password" => \'' . $db['db_password'] . '\',
                 "database" => \'' . $db['db_name'] . '\',
                 "port"     => \'' . $db['db_socket'] . '\',
                 "prefix"   => \'' . $db['db_prefix'] . '\',
                 "salt"     => \'' . $salt . '\',
                 "cipher"   => \'' . $ciper . '\',
                 "encoding" => "utf8mb4"
);';

        if ( file_put_contents($filename, $content) === FALSE )
        {
            echo '<span id="mooError">Cannot create file config</span>';
            die();
        }

        // create admin account
        $password = md5( $this->request->data['password'] . $salt );
        $code     = md5( $this->request->data['email'] . microtime() );
        mysqli_query($this->db_link,"INSERT INTO " . $db['db_prefix'] . "users ( id, name, email, password, role_id,avatar,photo, code, timezone, gender, birthday,last_login, created,photo_count,friend_count,notification_count,friend_request_count,blog_count,topic_count,conversation_user_count,video_count,active,confirmed,notification_email,ip_address,privacy,username,about,featured,lang,hide_online,cover,approved )
                     VALUES (" . ROOT_ADMIN_ID . ", '" .
            mysqli_real_escape_string($this->db_link, $this->request->data['name'] ) . "','" .
            mysqli_real_escape_string($this->db_link, $this->request->data['email'] ) . "','" .
            $password . "'," .
            ROLE_ADMIN . ",'" .
            "','" .
            "','" .
            $code . "','" .
            mysqli_real_escape_string($this->db_link, $this->request->data['timezone'] ) . "',
                             'Male',
                             NOW(),
                             NOW(),
                             NOW(),
                             0,
                             0,
                             0,
                             0,
                             0,
                             0,
                             0,
                             0,
                             1,
                             1,
                             1,
                             '',
                             1,
                             '',
                             '',
                             0,
                             '',
                             0,
                             '',
                             1)");

        if ( mysqli_error($this->db_link) )
        {
            echo '<span id="mooError">' . mysqli_error($this->db_link) . '</span>';
            die();
        }
        
        $this->Cookie->name = $this->request->base.'/mooSocial';
        $this->Cookie->key = $salt;
        $this->Cookie->delete('email');
        $this->Cookie->delete('password');

        $this->render('finish');
    }

    private function _connectDb( $data )
    {
        $host = $data['db_host'];

        if ( !empty( $data['db_socket'] ) )
            $host .= ':' . $data['db_socket'];

        $this->db_link = mysqli_connect( $host , $data['db_username'], $data['db_password'] );

        if ( !$this->db_link )
        {
            echo '<span id="mooError">' . mysqli_error($this->db_link) . '</span>';
            die();
        }

        $db_selected = mysqli_select_db($this->db_link, $data['db_name'] );

        if ( !$db_selected )
        {
            echo '<span id="mooError">' . mysqli_error($this->db_link) . '</span>';
            die();
        }
		
        mysqli_set_charset($this->db_link, "utf8mb4");
        //mysqli_query($this->db_link , "SET  SESSION sql_mode=''");

        $db_array = array( 'db_host'     => $data['db_host'],
            'db_socket'   => $data['db_socket'],
            'db_username' => $data['db_username'],
            'db_password' => $data['db_password'],
            'db_name'     => $data['db_name'],
            'db_prefix'   => $data['db_prefix']
        );

        return serialize( $db_array );
    }

    private function _checkConfigFile()
    {
        // check for config file
        if ( file_exists( APP . 'Config' . DS . 'config.php' ) )
        {
            $this->redirect( '/' );
            die();
        }
    }
    private function generateRandomString($length = 10) {
        $characters = '0123456789';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }
}
