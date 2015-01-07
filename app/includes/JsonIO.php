<?php
/**
 * Created by PhpStorm.
 * User: RiteshReddy
 * Date: 12/30/14
 * Time: 12:43 PM
 */

namespace app\includes;


class JsonIO
{
    const BAD_REQUEST = 400;

    public static function emit($data, $error = NULL)
    {

        $out = array("data" => $data);
        return json_encode($out, JSON_HEX_QUOT);

    }

    public static function json_decode_array($input)
    {
        $from_json = json_decode($input, true);
        return $from_json ? $from_json : $input;
    }

    public static function receive($json)
    {
        $arr = json_decode($json, true);
        if (isset($arr['data'])) {
            if (count($arr['data']) === 1) {
                return array_values($arr['data'])[0];
            } else {
                return $arr['data'];
            }

        } else {
            return FALSE;
        }
    }

    public static function emitSuccess($success, $detail = NULL, $code = 200)
    {
        return json_encode(array('sucCode' => $code, 'success' => $success, "detail" => $detail));
    }

    public static function emitError($error, $detail = NULL, $code = 400)
    {
        return json_encode(array('errCode' => $code, 'error' => $error, "detail" => $detail));
    }
} 