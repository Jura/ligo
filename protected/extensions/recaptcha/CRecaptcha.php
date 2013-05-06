<?php

class ReCaptchaResponse {
    var $is_valid;
    var $error;
}

class CRecaptcha extends CComponent {

    // private key
    protected static $_privkey = '6LdZH8ESAAAAAG57cm7ItKfVjVQgAzvcwlyCOLWo';

    public function check_answer ($remoteip, $challenge, $response, $extra_params = array())
    {

        //discard spam submissions
        if ($challenge == null || strlen($challenge) == 0 || $response == null || strlen($response) == 0) {
            $recaptcha_response = new ReCaptchaResponse();
            $recaptcha_response->is_valid = false;
            $recaptcha_response->error = 'incorrect-captcha-sol';
            return $recaptcha_response;
        }

        $response = self::http_post ('www.google.com', '/recaptcha/api/verify',
            array (
                'privatekey' => self::$_privkey,
                'remoteip' => $remoteip,
                'challenge' => $challenge,
                'response' => $response
            ) + $extra_params
        );

        $answers = explode ("\n", $response [1]);
        $recaptcha_response = new ReCaptchaResponse();

        if (trim ($answers [0]) == 'true') {
            $recaptcha_response->is_valid = true;
        }
        else {
            $recaptcha_response->is_valid = false;
            $recaptcha_response->error = $answers [1];
        }
        return $recaptcha_response;

    }

    protected function http_post($host, $path, $data, $port = 80) {

        $req = self::qsencode ($data);

        $http_request  = "POST $path HTTP/1.0\r\n";
        $http_request .= "Host: $host\r\n";
        $http_request .= "Content-Type: application/x-www-form-urlencoded;\r\n";
        $http_request .= "Content-Length: " . strlen($req) . "\r\n";
        $http_request .= "User-Agent: reCAPTCHA/PHP\r\n";
        $http_request .= "\r\n";
        $http_request .= $req;

        $response = '';
        if( false == ( $fs = @fsockopen($host, $port, $errno, $errstr, 10) ) ) {
            die ('Could not open socket');
        }

        fwrite($fs, $http_request);

        while ( !feof($fs) )
            $response .= fgets($fs, 1160); // One TCP-IP packet
        fclose($fs);
        $response = explode("\r\n\r\n", $response, 2);

        return $response;
    }

    protected function qsencode ($data) {
        $req = "";
        foreach ( $data as $key => $value )
            $req .= $key . '=' . urlencode( stripslashes($value) ) . '&';

        // Cut the last '&'
        $req=substr($req,0,strlen($req)-1);
        return $req;
    }


}

?>