<?php
require_once 'base-nmr-jsgrid.php';
class NmrGridRepo extends BaseNmrJsGrid
{
    public function __construct($tables, $method, $data)
    {
        parent::__construct();
        $this->tables = $tables;
        $this->method = $method;
        $this->data = $data;
    }

    public function Execute()
    {
        switch ($this->method) {
            case 'GET':
                $this->getdata();
                break;
            case 'PUT':
                $this->update();
                break;
            case 'POST':
                $this->create();
                break;
            case 'DELETE':
                $this->delete();
                break;
            default:
                $this->error = 'Not implemented';
                break;
        }
    }

    private function delete()
    {
        global $wpdb;
        $tables = $this->tables;

        $id = $this->get('id', 1);
        // delete only type 0 records
        $this->result = $wpdb->delete($tables['grid'], ['id' => $id, 'type' => 0], ['%d', '%d']);
    }

    private function create()
    {
        global $wpdb;
        $tables = $this->tables;
        $config = [
            'width' => $this->get('width', 2),
            'height' => $this->get('height', 2),
            'pageSize' => $this->get('pageSize', 1),
            'pagerFormat' => $this->get('pagerFormat', 2),
            'pageButtonCount' => $this->get('pageButtonCount', 1),
            'paging' => filter_var($this->get('paging', 2), FILTER_VALIDATE_BOOLEAN),
            'editing' => filter_var($this->get('editing', 2), FILTER_VALIDATE_BOOLEAN),
            'sorting' => filter_var($this->get('sorting', 2), FILTER_VALIDATE_BOOLEAN),
            'autoload' => filter_var($this->get('autoload', 2), FILTER_VALIDATE_BOOLEAN),
            'deleting' => filter_var($this->get('deleting', 2), FILTER_VALIDATE_BOOLEAN),
            'filtering' => filter_var($this->get('filtering', 2), FILTER_VALIDATE_BOOLEAN),
            'inserting' => filter_var($this->get('inserting', 2), FILTER_VALIDATE_BOOLEAN),
            'pageLoading' => filter_var($this->get('pageLoading', 2), FILTER_VALIDATE_BOOLEAN),
            'fields' => $this->get('fields', 2),
        ];
        $col_config = [
            'url' => $this->get('url', 2),
            'action' => $this->get('action2', 2),
            'config' => $config,
        ];
        $newData = [
            'name' => $this->get('name', 2),
            'config' => json_encode($col_config)
        ];

        $result = $wpdb->insert($tables['grid'], $newData, ['%s', '%s']);
        if ($result) {
            $sql = $wpdb->prepare("SELECT * FROM {$tables['grid']} WHERE id=%d", $wpdb->insert_id);
            $this->result = $this->to_frontend([$wpdb->get_row($sql, ARRAY_A)])[0];
        } else {
            $this->error = 'Could not insert data';
        }
    }

    private function update()
    {
        global $wpdb;
        $tables = $this->tables;
        $id = $this->get('id');
        $fields = null;
        if ($this->get('fields', 2) > '') {
            $fields = json_decode($this->get('fields', 2));
        }
        $config = [
            'width' => $this->get('width', 2),
            'height' => $this->get('height', 2),
            'pageSize' => $this->get('pageSize', 1),
            'pagerFormat' => $this->get('pagerFormat', 2),
            'pageButtonCount' => $this->get('pageButtonCount', 1),
            'paging' => filter_var($this->get('paging', 2), FILTER_VALIDATE_BOOLEAN),
            'editing' => filter_var($this->get('editing', 2), FILTER_VALIDATE_BOOLEAN),
            'sorting' => filter_var($this->get('sorting', 2), FILTER_VALIDATE_BOOLEAN),
            'autoload' => filter_var($this->get('autoload', 2), FILTER_VALIDATE_BOOLEAN),
            'deleting' => filter_var($this->get('deleting', 2), FILTER_VALIDATE_BOOLEAN),
            'filtering' => filter_var($this->get('filtering', 2), FILTER_VALIDATE_BOOLEAN),
            'inserting' => filter_var($this->get('inserting', 2), FILTER_VALIDATE_BOOLEAN),
            'pageLoading' => filter_var($this->get('pageLoading', 2), FILTER_VALIDATE_BOOLEAN),
            'fields' => $fields,
        ];
        $col_config = [
            'url' => $this->get('url', 2),
            'action' => $this->get('action2', 2),
            'config' => $config,
        ];
        $newData = [
            'name' => $this->get('name', 2),
            'config' => json_encode($col_config)
        ];

        $wpdb->update($tables['grid'], $newData, ['id' => $id], ['%s', '%s'], ['%d']);
        $sql = $wpdb->prepare("SELECT * FROM {$tables['grid']} WHERE id=%d", $id);
        $this->result = $this->to_frontend([$wpdb->get_row($sql, ARRAY_A)])[0];
    }

    private function to_frontend($rows)
    {
        $results = [];
        foreach ($rows as $row) {
            $d = json_decode($row['config'], true);
            if (is_null($d)) {
                continue;
            }
            //error_log(print_r($d, true));
            $c = $d['config'];
            $fields = null;
            //$fields = $c['fields'];
            if ($c['fields']) {
                $fields = json_encode($c['fields']);
            }
            $new = [
                'id' => $row['id'],
                'name' => $row['name'],
                'url' => $d['url'],
                'action2' => $d['action'],
                'width' => $c['width'],
                'height' => $c['height'],
                'paging' => $c['paging'],
                'editing' => $c['editing'],
                'sorting' => $c['sorting'],
                'autoload' => $c['autoload'],
                'deleting' => $c['deleting'],
                'filtering' => $c['filtering'],
                'inserting' => $c['inserting'],
                'pageLoading' => $c['pageLoading'],
                'pageSize' => $c['pageSize'],
                'pagerFormat' => $c['pagerFormat'],
                'pageButtonCount' => $c['pageButtonCount'],
                'fields' => $fields,  // encode back to string to show the string in the column
            ];
            $results[] = $new;
        }
        return $results;
    }

    private function getdata()
    {
        global $wpdb;
        $tables = $this->tables;
        $where = '';
        $sql_base = $wpdb->prepare("SELECT * 
            FROM {$tables['grid']} 
            WHERE type=0 {$where}");
        $sql = "SELECT COUNT(*)
            FROM ({$sql_base}) main";
        $itemsCount = $wpdb->get_var($sql);
        $order = $this->get('sortField', 2);
        $desc =  $this->get('sortOrder', 2);
        if ($order) {
            $order = "ORDER BY $order";
            if ($desc) {
                $order .= " $desc";
            }
        } else {
        }
        $pageIndex = intval($this->get('pageIndex', 1));
        if ($pageIndex < 1)
            $pageIndex = 1;
        $pageSize = intval($this->get('pageSize', 1));
        if ($pageSize < 1) {
            $pageSize = 20;
        }
        $endIndex = $pageIndex * $pageSize;
        $startIndex = $endIndex - $pageSize;
        $limit = "LIMIT {$pageSize} OFFSET {$startIndex}";

        $sql = "SELECT t.* 
            FROM ({$sql_base} {$order}) t
            {$limit}";
        $rows = $wpdb->get_results($sql, ARRAY_A);
        if (null === $rows) {
            wp_send_json_error('nmr jsgrid plugin table not found', 400);
            return;
        }

        $results = $this->to_frontend($rows);
        $this->result = array(
            'data' => $results,
            'itemsCount' => $itemsCount,
        );
    }
}
