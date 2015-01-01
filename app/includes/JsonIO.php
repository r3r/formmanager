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
        return json_encode($out);
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

    public static function emitError($error, $code = 400, $detail = NULL)
    {
        return json_encode(array('errCode' => $code, 'error' => $error, "detail" => $detail));
    }
} 