<?php
// /opt/panel/www/classes/TOTP.php

class TOTP {
    private static $base32chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public static function generateSecret($length = 16) {
        $secret = '';
        for ($i = 0; $i < $length; $i++) {
            $secret .= self::$base32chars[random_int(0, 31)];
        }
        return $secret;
    }

    public static function getQRCodeUrl($name, $secret, $issuer = 'oPanel') {
        $url = "otpauth://totp/" . rawurlencode($issuer) . ":" . rawurlencode($name) . "?secret=" . $secret . "&issuer=" . rawurlencode($issuer);
        return "https://api.qrserver.com/v1/create-qr-code/?data=" . rawurlencode($url) . "&size=200x200&ecc=M";
    }

    public static function verifyCode($secret, $code, $discrepancy = 1) {
        $currentTimeSlice = floor(time() / 30);
        for ($i = -$discrepancy; $i <= $discrepancy; $i++) {
            $calculatedCode = self::getCode($secret, $currentTimeSlice + $i);
            if (hash_equals($calculatedCode, $code)) {
                return true;
            }
        }
        return false;
    }

    private static function getCode($secret, $timeSlice) {
        $secretKey = self::base32Decode($secret);
        $time = pack('N*', 0) . pack('N*', $timeSlice);
        $hmac = hash_hmac('sha1', $time, $secretKey, true);
        $offset = ord(substr($hmac, -1)) & 0x0F;
        $hashPart = substr($hmac, $offset, 4);
        $value = unpack('N', $hashPart);
        $value = $value[1] & 0x7FFFFFFF;
        $modulo = pow(10, 6);
        return str_pad($value % $modulo, 6, '0', STR_PAD_LEFT);
    }

    private static function base32Decode($secret) {
        if (empty($secret)) return '';
        $paddingCharCount = substr_count($secret, '=');
        $allowedValues = array_flip(str_split(self::$base32chars));
        $out = '';
        for ($i = 0; $i < strlen($secret); $i++) {
            $out .= str_pad(base_convert($allowedValues[$secret[$i]], 10, 2), 5, '0', STR_PAD_LEFT);
        }
        $out = substr($out, 0, strlen($out) - ($paddingCharCount * 5));
        $decoded = '';
        for ($i = 0; $i < strlen($out); $i += 8) {
            $decoded .= chr(bindec(substr($out, $i, 8)));
        }
        return $decoded;
    }
}