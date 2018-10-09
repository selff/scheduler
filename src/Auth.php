<?php
/**
 * Created by PhpStorm.
 * User: andreyselikov
 * Date: 09.10.2018
 * Time: 20:40
 */

namespace Schedule;


class Auth
{
    public static function nodb($user,$pass) {

        if (md5($user) == '21232f297a57a5a743894a0e4a801fc3' && md5($pass) == '499f31e79c00c9e8f61bdaadc5e82f45') {
            return true;
        } else {
            return false;
        }

    }
}