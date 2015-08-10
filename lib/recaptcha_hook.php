<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

if ( ! class_exists( 'ReCaptchaResponse_v' ) ){
    class ReCaptchaResponse_v
    {
        public $success;
        public $errorCodes;
    }
}

if ( ! class_exists( 'ReCaptcha_g5' ) ){
    class ReCaptcha_g5
    {
        private static $_signupUrl = "https://www.google.com/recaptcha/admin";
        private static $_siteVerifyUrl =
            "https://www.google.com/recaptcha/api/siteverify?";
        private $_secret;
        private static $_version = "php_1.0";
        /**
         * Constructor.
         *
         * @param string $secret shared secret between site and ReCAPTCHA server.
         */
        function ReCaptcha_g5($secret)
        {
            if ($secret == null || $secret == "") {
                die("To use reCAPTCHA you must get an API key from <a href='"
                    . self::$_signupUrl . "'>" . self::$_signupUrl . "</a>");
            }
            $this->_secret=$secret;
        }
        /**
         * Encodes the given data into a query string format.
         *
         * @param array $data array of string elements to be encoded.
         *
         * @return string - encoded request.
         */
        private function _encodeQS($data)
        {
            $req = "";
            foreach ($data as $key => $value) {
                $req .= $key . '=' . urlencode(stripslashes($value)) . '&';
            }
            // Cut the last '&'
            $req=substr($req, 0, strlen($req)-1);
            return $req;
        }
        /**
         * Submits an HTTP GET to a reCAPTCHA server.
         *
         * @param string $path url path to recaptcha server.
         * @param array  $data array of parameters to be sent.
         *
         * @return array response
         */
        private function _submitHTTPGet($path, $data)
        {
            $req = $this->_encodeQS($data);
            $response = $this->get_content($path . $req);
            return $response;
        }

        public function get_content($url) {
        $agent = 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.0)';
        $curlsession = curl_init ();
        curl_setopt ($curlsession, CURLOPT_URL, $url);
        curl_setopt ($curlsession, CURLOPT_HEADER, 0);
        curl_setopt ($curlsession, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt ($curlsession, CURLOPT_POST, 0);
        curl_setopt ($curlsession, CURLOPT_USERAGENT, $agent);
        curl_setopt ($curlsession, CURLOPT_REFERER, "");
        curl_setopt ($curlsession, CURLOPT_TIMEOUT, 3);
        $buffer = curl_exec ($curlsession);
        $cinfo = curl_getinfo($curlsession);
        curl_close($curlsession);
        if ($cinfo['http_code'] != 200)
        {
        return "";
        }
        return $buffer;
        }
        /**
         * Calls the reCAPTCHA siteverify API to verify whether the user passes
         * CAPTCHA test.
         *
         * @param string $remoteIp   IP address of end user.
         * @param string $response   response string from recaptcha verification.
         *
         * @return ReCaptchaResponse_v
         */
        public function verifyResponse($remoteIp, $response)
        {
            // Discard empty solution submissions
            if ($response == null || strlen($response) == 0) {
                $recaptchaResponse = new ReCaptchaResponse_v();
                $recaptchaResponse->success = false;
                $recaptchaResponse->errorCodes = 'missing-input';
                return $recaptchaResponse;
            }
            $getResponse = $this->_submitHttpGet(
                self::$_siteVerifyUrl,
                array (
                    'secret' => $this->_secret,
                    'remoteip' => $remoteIp,
                    'v' => self::$_version,
                    'response' => $response
                )
            );
            $answers = json_decode($getResponse, true);
            $recaptchaResponse = new ReCaptchaResponse_v();
            if (trim($answers ['success']) == true) {
                $recaptchaResponse->success = true;
            } else {
                $recaptchaResponse->success = false;
                $recaptchaResponse->errorCodes = $answers [error-codes];
            }
            return $recaptchaResponse;
        }
    }
}

if( ! function_exists('g5_captcha_get_html') ){
    add_filter('g5_captcha_get_html', 'g5_return_recaptcha_html');
    function g5_return_recaptcha_html($code=''){

        //$lang = 'ko';   //Language codes :: https://developers.google.com/recaptcha/docs/language?hl=ko
        $html = '';
        $html .= '<div id="recaptcha_div"></div>';
        add_action( 'wp_footer', 'g5_return_recaptcha_api' );
        return $html;
    }
}

if( ! function_exists('g5_return_recaptcha_api') ){
    function g5_return_recaptcha_api(){
        global $gnupress;

        $config = $gnupress->config;

        echo "<script>
                var g5_recaptcha_callback = function() {
                    grecaptcha.render('recaptcha_div', {
                        'sitekey' : '".$config['cf_recaptcha_site_key']."'
                    });
                };
                </script>";
        echo '<script src="https://www.google.com/recaptcha/api.js?onload=g5_recaptcha_callback&render=explicit" async defer></script>';
    }
}

if( ! function_exists('g5_return_recaptcha_action') ){
    add_filter('g5_captcha_action_check', 'g5_return_recaptcha_action');
    function g5_return_recaptcha_action(){
        global $gnupress;

        $config = $gnupress->config;

        // The response from reCAPTCHA
        $resp = null;

        if ( isset($_POST["g-recaptcha-response"]) && !empty($_POST["g-recaptcha-response"]) ) {

            $reCaptcha = new ReCaptcha_g5( $config['cf_recaptcha_secret_key'] );

            $resp = $reCaptcha->verifyResponse(
                $_SERVER["REMOTE_ADDR"],
                $_POST["g-recaptcha-response"]
            );
        }

        if ($resp != null && $resp->success) {
            return true;
        }

        return false;
    }
}

if( ! function_exists('g5_return_recaptcha_script') ){
    add_filter('g5_captcha_javascript', 'g5_return_recaptcha_script');
    function g5_return_recaptcha_script($code=''){
        ob_start();
    ?>
        var captcha_result = false,
            $captcha = $("#g-recaptcha-response"),
            captcha_val = $captcha.val();

        if( !captcha_val ){
            alert("자동등록방지를 반드시 체크해야 합니다.");
            return false;
        }
    <?php
        $output = ob_get_contents();
        ob_end_clean();

        return $output;
    }
}
?>