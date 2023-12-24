<?php
namespace Abstraction\Core;

use Abstraction\Contract\Payment\ITransactionInfo;
use Abstraction\Contract\PaymentGateway\IPaymentGateway;

class Helper
{
    /**
     * @param $string
     *
     * @return null|string|string[]
     */
    public static function underscoreToCamelCase( $string )
    {
        $func = create_function('$c', 'return strtoupper($c[1]);');
        return preg_replace_callback('/_([a-z])/', $func, $string);
    } // end underscore to camel case

    /**
     * @param $input
     *
     * @return string
     */
    public static function camelCaseToUnderScore($input)
    {
        preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $input, $matches);
        $ret = $matches[0];
        foreach ($ret as &$match) {
            $match = $match == strtoupper($match) ? strtolower($match) : lcfirst($match);
        }
        return implode('_', $ret);
    }

    public static function getGUID($str){
        $charid = strtoupper($str);
        $hyphen = chr(45);// "-"
        $uuid = substr($charid, 0, 8).$hyphen
            .substr($charid, 8, 4).$hyphen
            .substr($charid,12, 4).$hyphen
            .substr($charid,16, 4).$hyphen
            .substr($charid,20,12);
        return $uuid;
    }

    public static function isGUID($str)
    {
        if (preg_match('/^\{?[A-Z0-9]{8}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{12}\}?$/', $str)) {
            return true;
        } else {
            return false;
        }
    }

    public static function addZeroToRestaurantCode($code, $numberOfCharacter)
    {
        if (strlen($code) > $numberOfCharacter) {
            throw new \Exception('Mã nhà hàng lớn hơn số ký tự cần convert');
        }

        while (strlen($code) < $numberOfCharacter) {
            $code = '0'.$code;
        }

        return $code;
    }

    public static function convertUnsignedVietnamese($str)
    {
        $coDau=array("à","á","ạ","ả","ã","â","ầ","ấ","ậ","ẩ","ẫ","ă","ằ","ắ"
        ,"ặ","ẳ","ẵ","è","é","ẹ","ẻ","ẽ","ê","ề","ế","ệ","ể","ễ","ì","í","ị","ỉ","ĩ",
            "ò","ó","ọ","ỏ","õ","ô","ồ","ố","ộ","ổ","ỗ","ơ"
        ,"ờ","ớ","ợ","ở","ỡ",
            "ù","ú","ụ","ủ","ũ","ư","ừ","ứ","ự","ử","ữ",
            "ỳ","ý","ỵ","ỷ","ỹ",
            "đ",
            "À","Á","Ạ","Ả","Ã","Â","Ầ","Ấ","Ậ","Ẩ","Ẫ","Ă"
        ,"Ằ","Ắ","Ặ","Ẳ","Ẵ",
            "È","É","Ẹ","Ẻ","Ẽ","Ê","Ề","Ế","Ệ","Ể","Ễ",
            "Ì","Í","Ị","Ỉ","Ĩ",
            "Ò","Ó","Ọ","Ỏ","Õ","Ô","Ồ","Ố","Ộ","Ổ","Ỗ","Ơ"
        ,"Ờ","Ớ","Ợ","Ở","Ỡ",
            "Ù","Ú","Ụ","Ủ","Ũ","Ư","Ừ","Ứ","Ự","Ử","Ữ",
            "Ỳ","Ý","Ỵ","Ỷ","Ỹ",
            "Đ","ê","ù","à");
        $khongDau=array("a","a","a","a","a","a","a","a","a","a","a"
        ,"a","a","a","a","a","a",
            "e","e","e","e","e","e","e","e","e","e","e",
            "i","i","i","i","i",
            "o","o","o","o","o","o","o","o","o","o","o","o"
        ,"o","o","o","o","o",
            "u","u","u","u","u","u","u","u","u","u","u",
            "y","y","y","y","y",
            "d",
            "A","A","A","A","A","A","A","A","A","A","A","A"
        ,"A","A","A","A","A",
            "E","E","E","E","E","E","E","E","E","E","E",
            "I","I","I","I","I",
            "O","O","O","O","O","O","O","O","O","O","O","O"
        ,"O","O","O","O","O",
            "U","U","U","U","U","U","U","U","U","U","U",
            "Y","Y","Y","Y","Y",
            "D","e","u","a");

        $str = str_replace($coDau,$khongDau,$str);

        $str = htmlspecialchars($str);

        return $str;
    } // end cover unsigned vietnamese

    public static function generateSlug($str)
    {
        $coDau = array("à","á","ạ","ả","ã","â","ầ","ấ","ậ","ẩ","ẫ","ă","ằ","ắ"
        ,"ặ","ẳ","ẵ","è","é","ẹ","ẻ","ẽ","ê","ề","ế","ệ","ể","ễ","ì","í","ị","ỉ","ĩ",
            "ò","ó","ọ","ỏ","õ","ô","ồ","ố","ộ","ổ","ỗ","ơ"
        ,"ờ","ớ","ợ","ở","ỡ",
            "ù","ú","ụ","ủ","ũ","ư","ừ","ứ","ự","ử","ữ",
            "ỳ","ý","ỵ","ỷ","ỹ",
            "đ",
            "À","Á","Ạ","Ả","Ã","Â","Ầ","Ấ","Ậ","Ẩ","Ẫ","Ă"
        ,"Ằ","Ắ","Ặ","Ẳ","Ẵ",
            "È","É","Ẹ","Ẻ","Ẽ","Ê","Ề","Ế","Ệ","Ể","Ễ",
            "Ì","Í","Ị","Ỉ","Ĩ",
            "Ò","Ó","Ọ","Ỏ","Õ","Ô","Ồ","Ố","Ộ","Ổ","Ỗ","Ơ"
        ,"Ờ","Ớ","Ợ","Ở","Ỡ",
            "Ù","Ú","Ụ","Ủ","Ũ","Ư","Ừ","Ứ","Ự","Ử","Ữ",
            "Ỳ","Ý","Ỵ","Ỷ","Ỹ",
            "Đ","ê","ù","à");
        $khongDau = array("a","a","a","a","a","a","a","a","a","a","a"
        ,"a","a","a","a","a","a",
            "e","e","e","e","e","e","e","e","e","e","e",
            "i","i","i","i","i",
            "o","o","o","o","o","o","o","o","o","o","o","o"
        ,"o","o","o","o","o",
            "u","u","u","u","u","u","u","u","u","u","u",
            "y","y","y","y","y",
            "d",
            "A","A","A","A","A","A","A","A","A","A","A","A"
        ,"A","A","A","A","A",
            "E","E","E","E","E","E","E","E","E","E","E",
            "I","I","I","I","I",
            "O","O","O","O","O","O","O","O","O","O","O","O"
        ,"O","O","O","O","O",
            "U","U","U","U","U","U","U","U","U","U","U",
            "Y","Y","Y","Y","Y",
            "D","e","u","a");
        return mb_strtolower(
            preg_replace(
                ['/[^a-zA-Z0-9 -]/', '/[ -]+/', '/^-|-$/'],
                ['', '-', ''],
                str_replace($coDau,$khongDau,$str)
            )
        );
    } // end generate slug

    public static function removeFeedLineFromString($string)
    {
        $string = str_replace("\n", "", $string);
        $string = str_replace("\t", "", $string);
        $string = str_replace("\r", "", $string);

        return $string;
    }

    /**
     * @param String $uidString
     * @param int $length
     *
     * @return String
     * @throws \Exception
     */
    public static function generateUniqueStringByLength(String $uidString, int $length)
    {
        $strlenlUid = strlen($uidString);

        if ($strlenlUid > $length) {
            throw new \Exception('UidString lớn hơn số ký tự cần convert');
        } else if ($strlenlUid == $length) {
            return $uidString;
        }

        $randomString = strtolower(str_random($length - $strlenlUid));
        $uidString    = $uidString.$randomString;

        return $uidString;
    }

    /**
     * Return valid cellphone number start with 0; eg: 0987802175
     * @param $phoneNumber
     *
     * @return bool|string
     */
    public static function correctCellphoneNumber($phoneNumber)
    {
        if (substr($phoneNumber, 0, 3) === '840') {
            $phoneNumber = '0'.substr($phoneNumber, 3);
        }  elseif (substr($phoneNumber, 0, 4) === '+840') {
            $phoneNumber = '0'.substr($phoneNumber, 4);
        } elseif (substr($phoneNumber, 0, 3) === '+84') {
            $phoneNumber = '0'.substr($phoneNumber, 3);
        } elseif (substr($phoneNumber, 0, 2) === '84') {
            $phoneNumber = '0'.substr($phoneNumber, 2);
        } elseif (substr($phoneNumber, 0, 1) !== '0') {
            $phoneNumber = '0'.$phoneNumber;
        } elseif (substr($phoneNumber, 0, 1) === '0') {

        } else {
            return false;
        }

        // check phone Vietnamese
        if (preg_match('/^(0|\+84)(\s|\.)?((3[2-9])|(5[689])|(7[06-9])|(8[1-689])|(9[0-46-9]))(\d)(\s|\.)?(\d{3})(\s|\.)?(\d{3})$/',$phoneNumber)) {
            return $phoneNumber;
        } else {
            return false;
        }
    }

    public static function correctClmCustomerNumber($customerNumber)
    {
        if (\strlen($customerNumber) < 8) {
            $len = 8 - \strlen($customerNumber);
            for ($i = 1; $i <= $len; $i++) {
                $customerNumber = '0'.$customerNumber;
            }
        }

        return $customerNumber;
    }
} // end class
