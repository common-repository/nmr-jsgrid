<?php
/*
Plugin Name: NMR jsGrid
Plugin URI: https://namir.ro/jsgrid/
Description: Create ajax paginated tables in wordpress based on jsGrid (jquery plugin)
Author: Mircea N.
Text Domain: nmr-jsgrid
Domain Path: /languages/
Version: 1.0.0
*/

include_once 'base-nmr-jsgrid.php';
class JSGridNmr extends BaseNmrJsGrid
{
    private static $db_version = '1.0';
    public function __construct()
    {
        $this->init_plugin();
    }

    function init_plugin()
    {
        global $wpdb;
        self::$tables = [
            'grid' => "{$wpdb->prefix}nmr_jsgrid",
        ];
        register_activation_hook(__FILE__, ['JSGridNmr', 'install']);
        add_action('plugins_loaded', ['JSGridNmr', 'update_db_check']);
        add_action('admin_menu', ['JSGridNmr', 'setup_admin_menu']);
        add_action('init', ['JSGridNmr', 'shortcodes_init']);
        add_action('wp_enqueue_scripts', ['JSGridNmr', 'scripts_for_profile_page']);
        add_action('admin_enqueue_scripts', ['JSGridNmr', 'scripts_for_admin']);
        add_action('wp_ajax_nmrjsgridconfig', ['JSGridNmr', 'config_callback']);
        add_action('wp_ajax_nopriv_nmrjsgridconfig', ['JSGridNmr', 'config_callback']);
        add_action('wp_ajax_nmr-jsgrid', ['JSGridNmr', 'admin_grid_callback']);
    }

    static function setup_admin_menu()
    {
        // Add an item to the menu.
        add_menu_page(
            __('NMR jsGrid', 'nmr-jsgrid'),
            __('NMR jsGrid', 'nmr-jsgrid'),
            'manage_options',
            'nmr-jsgrid',
            ['JSGridNmr', 'plugin_admin_page_render'],
            'dashicons-index-card'
        );
    }

    static function plugin_admin_page_render()
    {
        echo "<div class='wrap'>";
        echo "<h2>NMR jsGrids</h2>";
        echo do_shortcode("[nmr_jsgrid id='nmr-jsgrid' type=1]");
        echo "</div>";
    }

    static function scripts_for_admin($hook)
    {
        $admin_php_files = [];
        $admin_php_files = apply_filters('nmr_jsgrid_admin_enqueue_scripts', $admin_php_files);
        $admin_php_files[] = 'toplevel_page_nmr-jsgrid';
        if (is_array($admin_php_files) && in_array($hook, $admin_php_files)) {
            JSGridNmr::scripts_for_profile_page();
        }
    }

    static function scripts_for_profile_page()
    {
        wp_enqueue_style('jsgrid-css', plugins_url('/jsgrid.min.css', __FILE__));
        wp_enqueue_style('jsgrid-theme-css', plugins_url('/jsgrid-theme.min.css', __FILE__));
        wp_enqueue_script('jsgrid', plugins_url('/jsgrid.min.js', __FILE__), array('jquery'));

        wp_enqueue_script(
            'nmr-jsgrid',
            plugins_url('/nmr-jsgrid.js', __FILE__),
            array('jsgrid'),
            '1.1.0'
        );
        wp_localize_script('nmr-jsgrid', 'nmrapi', ['url' => admin_url('admin-ajax.php') . '?action=nmrjsgridconfig']);
    }

    static function admin_grid_callback()
    {
        if (!current_user_can('edit_posts')) {
            wp_send_json('Unknown', 401);
            return;
        }
        $data = array();
        switch ($_SERVER["REQUEST_METHOD"]) {
            case 'GET':
                $data = $_GET;
                break;
            case 'PUT':
            case 'DELETE':
                parse_str(file_get_contents('php://input'), $data);
                break;
            case 'POST':
                $data = $_POST;
                break;
            default:
                wp_send_json_error('Unknown verb', 400);
                return;
        }
        require_once 'grid-repo.php';
        $repo = new NmrGridRepo(self::$tables, $_SERVER["REQUEST_METHOD"], $data);
        $repo->Execute();
        if ($repo->IsError()) {
            wp_send_json_error($repo->GetError(), 400);
        } else {
            wp_send_json($repo->GetResult());
        }
    }

    static function config_callback()
    {
        $data = array();
        switch ($_SERVER["REQUEST_METHOD"]) {
            case 'GET':
                $data = $_GET;
                break;
            default:
                wp_send_json_error('Unknown verb', 400);
                return;
        }
        if (!is_array($data['ids']) || count($data['ids']) < 1) {
            wp_send_json_error('no grids');
        }
        $ids = array_map(function ($v) {
            return "'" . esc_sql($v) . "'";
        }, $data['ids']);
        $ids = implode(',', $ids);
        $result = [];
        $error_message = '';
        $has_error = false;
        $compile_grids = function ($ids) {
            global $wpdb;
            $tables = self::$tables;
            $sql = $wpdb->prepare("SELECT * FROM {$tables['grid']} 
                WHERE config IS NOT null AND name IN ({$ids})");
            $rows = $wpdb->get_results($sql, ARRAY_A);
            foreach ($rows as $key => $row) {
                $rows[$key] = array_merge($row, json_decode($row['config'], true));
            }
            return $rows;
        };
        $result = $compile_grids($ids);
        if ($has_error) {
            wp_send_json_error($error_message, 400);
        } else {
            wp_send_json($result);
        }
    }

    /**
     * Central location to create all shortcodes.
     */
    static function shortcodes_init()
    {
        add_shortcode('nmr_jsgrid', ['JSGridNmr', 'nmr_jsgrid_func']);
    }

    static function nmr_jsgrid_func($atts = [], $content = null, $tag = '')
    {
        global $wpdb;
        $a = shortcode_atts([
            'id' => '',
            'type' => '0',
        ], $atts);
        $output = '';
        $type = 0;
        $s_type = '';
        if ($a['type' <> '0']) {
            $type = intval($a['type']);
            if ($type <> 0) {
                $s_type = ' data-nmrjsgridtype="' . $type . '"';
            }
        }
        if ($a['id'] > '') {
            $output = "<div id=\"{$a['id']}\" data-nmrtype=\"jsgrid\"{$s_type}></div>";
        }
        return $output;
    }

    static function install()
    {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $tables = self::$tables;
        $sql[] = "CREATE TABLE {$tables['grid']} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            type int NOT NULL DEFAULT 0,
            name varchar(64) NOT NULL,
            config TEXT NOT NULL,
            last_change datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE (type, name)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        // update 1 row that holds definition of table we use in the admin to edit other tables. type==1 means system jsgrid
        $wpdb->replace(
            $tables['grid'],
            [
                'type' => 1,
                'name' => 'nmr-jsgrid',
                'config' => '{
                "url": "' . admin_url('admin-ajax.php') . '",
                "name": "nmr-jsgrid",
                "action": "nmr-jsgrid",
                "config": {
                    "width": "100%",
                    "fields": [
                        {
                            "name": "id",
                            "type": "number",
                            "title": "Id",
                            "width": 50,
                            "editing": false,
                            "inserting": false
                        },
                        {
                            "name": "name",
                            "type": "text",
                            "title": "Name",
                            "width": 50,
                            "editing": true
                        },
                        {
                            "name": "url",
                            "type": "text",
                            "title": "URL",
                            "width": 120,
                            "editing": true,
                            "filtering": true
                        },
                        {
                            "name": "action2",
                            "type": "text",
                            "title": "Action",
                            "width": 80,
                            "editing": true,
                            "filtering": true
                        },
                        {
                            "name": "fields",
                            "type": "simpleJson",
                            "title": "Fields",
                            "width": 120,
                            "editing": true,
                            "filtering": false
                        },
                        {
                            "name": "height",
                            "type": "text",
                            "title": "Height",
                            "width": 50,
                            "editing": true,
                            "filtering": true
                        },
                        {
                            "name": "width",
                            "type": "text",
                            "title": "Width",
                            "width": 50,
                            "editing": true,
                            "filtering": true
                        },
                        {
                            "name": "paging",
                            "type": "checkbox",
                            "title": "Paging",
                            "width": 50,
                            "editing": true,
                            "filtering": true
                        },
                        {
                            "name": "editing",
                            "type": "checkbox",
                            "title": "Editing",
                            "width": 50,
                            "editing": true,
                            "filtering": true
                        },
                        {
                            "name": "sorting",
                            "type": "checkbox",
                            "title": "Sorting",
                            "width": 50,
                            "editing": true,
                            "filtering": true
                        },
                        {
                            "name": "autoload",
                            "type": "checkbox",
                            "title": "Autoload",
                            "width": 50,
                            "editing": true,
                            "filtering": true
                        },
                        {
                            "name": "deleting",
                            "type": "checkbox",
                            "title": "Deleting",
                            "width": 50,
                            "editing": true,
                            "filtering": true
                        },
                        {
                            "name": "filtering",
                            "type": "checkbox",
                            "title": "Filtering",
                            "width": 50,
                            "editing": true,
                            "filtering": true
                        },
                        {
                            "name": "inserting",
                            "type": "checkbox",
                            "title": "Inserting",
                            "width": 50,
                            "editing": true,
                            "filtering": true
                        },
                        {
                            "name": "pageLoading",
                            "type": "checkbox",
                            "title": "Page loading",
                            "width": 50,
                            "editing": true,
                            "filtering": true
                        },
                        {
                            "name": "pageSize",
                            "type": "number",
                            "title": "Page size",
                            "width": 80,
                            "editing": true,
                            "filtering": true
                        },
                        {
                            "name": "pagerFormat",
                            "type": "text",
                            "title": "Pager format",
                            "width": 120,
                            "editing": true,
                            "filtering": true
                        },
                        {
                            "name": "pageButtonCount",
                            "type": "number",
                            "title": "Page buttons",
                            "width": 30,
                            "editing": true,
                            "filtering": true
                        },
                        {
                            "type": "control",
                            "align": "center",
                            "editButton": true,
                            "deleteButton": true
                        }
                    ],
                    "height": "auto",
                    "paging": true,
                    "editing": true,
                    "sorting": true,
                    "autoload": true,
                    "deleting": true,
                    "pageSize": 20,
                    "filtering": true,
                    "inserting": true,
                    "controller": {},
                    "pageLoading": true,
                    "pagerFormat": "Rows: {itemCount} - Pages: {first} {prev} {pages} {next} {last} &nbsp;&nbsp; {pageIndex} of {pageCount}",
                    "pageButtonCount": 5
                }
            }'
            ],
            ['%d', '%s', '%s']
        );
        update_option('nmr_jsgrid_db_version', self::$db_version);
    }

    static function update_db_check()
    {
        if (
            get_site_option('nmr_jsgrid_db_version')
            != self::$db_version
        ) {
            self::install();
        }
    }
}

$instance = new JSGridNmr();
