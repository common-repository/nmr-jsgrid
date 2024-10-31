<?php
class BaseNmrJsGrid
{
    public static $tables = [];
    protected $error;
    protected $result;
    protected $method;
    protected $data;

    public function __construct()
    {
    }

    public function IsError()
    {
        if ($this->error)
            return true;
        return false;
    }

    public function GetError()
    {
        return $this->error;
    }

    public function GetResult()
    {
        return $this->result;
    }

    protected function get($key, $type = 1)
    {
        $type = intval($type);
        if (!$this->data || !array_key_exists($key, $this->data))
            return null;
        $result = $this->data[$key];
        switch ($type) {
            case 1:
                if (is_null($result) || '' === $result) {
                    return null;
                }
                return intval($result);
            case 2:
                return sanitize_text_field($result);
        }
        return null;
    }

    public function set_error($errMsg, $user_id = null, $strava_user_id = null, $arrDetails = array())
    {
        global $wpdb;
        $this->error = $errMsg;
        $dataString = '';
        if (count($arrDetails) > 0) {
            $dataString = print_r($arrDetails, true);
        }
        $wpdb->insert(
            self::$tables['logs'],
            [
                'strava_user_id' => $strava_user_id,
                'user_id' => $user_id,
                'message' => "{$errMsg} {$dataString}"
            ],
            ['%d', '%d', '%s']
        );
    }

    static function log($message, $user_id = null, $strava_user_id = null, $arrDetails = array())
    {
        global $wpdb;
        $dataString = '';
        if (count($arrDetails) > 0) {
            $dataString = print_r($arrDetails, true);
        }
        $wpdb->insert(
            self::$tables['logs'],
            [
                'strava_user_id' => $strava_user_id,
                'user_id' => $user_id,
                'message' => "{$message} {$dataString}"
            ],
            ['%d', '%d', '%s']
        );
    }
}
