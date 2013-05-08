<?php
    @session_start();


    interface importEmailInterface {
        public function isConnected();
        public function redirect($popup);
        public function logout();
        public function refreshToken();
        public function getToken();
        public function getContacts();
    }

    class importGmail implements importEmailInterface {

        private $client_id;
        private $client_secret;
        private $redirect_uri;
        private $max_results;

        function __construct($client_id, $client_secret, $redirect_uri, $max_results)
        {
            $this->client_id = $client_id;
            $this->client_secret = $client_secret;
            $this->redirect_uri = $redirect_uri;
            $this->max_results = $max_results;
        }

        public function isConnected()
        {
            $access_token = $this->session_get();
            if ( !empty( $access_token )  )
                return true;
            if ( isset( $_GET['code']) ) {
                $access_token = self::getAccessToken($_GET['code']);
                if ( !empty( $access_token) ) {
                    $this->session_save($access_token);
                    return true;
                }
            }

            return false;
        }

        private function getAccessToken( $auth_code ) {
            $fields=array(
                'code'=>  urlencode($auth_code),
                'client_id'=>  urlencode($this->client_id),
                'client_secret'=>  urlencode($this->client_secret),
                'redirect_uri'=>  urlencode($this->redirect_uri),
                'grant_type'=>  urlencode('authorization_code')
            );
            $post = '';
            foreach($fields as $key=>$value) { $post .= $key.'='.$value.'&'; }
            $post = rtrim($post,'&');
            $result = $this->curl_request('https://accounts.google.com/o/oauth2/token',$post,5);
            //echo $result;
            if ( !empty($result) ) {
                $response =  json_decode($result);
                $accesstoken = $response->access_token;
                if ( !empty($accesstoken) )
                    return $accesstoken;
            }
            return "";
        }

        public function redirect($popup)
        {
            header('Location: https://accounts.google.com/o/oauth2/auth?client_id='.$this->client_id.'&redirect_uri='.$this->redirect_uri.'&scope=https://www.google.com/m8/feeds/&response_type=code');
        }

        public function logout()
        {
            $this->session_save('');
            return true;
        }

        public function refreshToken()
        {
            return true;
        }

        public function getToken()
        {
            return $this->session_get();
        }

        public function getContacts()
        {
            if ( ! $this->isConnected() )
                return "";
            $access_token = $this->session_get();

            $url = 'https://www.google.com/m8/feeds/contacts/default/full?max-results='.$this->max_results.'&oauth_token='.$access_token;
            $xmlresponse =  $this->curl_request($url);
            if((strlen(stristr($xmlresponse,'Authorization required'))>0) && (strlen(stristr($xmlresponse,'Error '))>0))
                return "";

            $xml =  new SimpleXMLElement($xmlresponse);
            $xml->registerXPathNamespace('gd', 'http://schemas.google.com/g/2005');
            $result = $xml->xpath('//gd:email');
            $count = 0;
            $out = array();
            foreach ($result as $title) {
                $outT['title']      = (string) $xml->entry[$count++]->title;
                $outT['address']    = (string) $title->attributes()->address;
                $out[] =  $outT;
            }

            return $out;
        }

        private function session_save($access_token) {
            $_SESSION['gmail']['access_token'] = $access_token;
        }

        private function session_get() {
            if ( array_key_exists('gmail', $_SESSION) && array_key_exists('access_token', $_SESSION['gmail']) )
                return $_SESSION['gmail']['access_token'];
            return "";
        }

        private function curl_request($url,$post="",$num_post=0) {
            $curl = curl_init();
            $userAgent = 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; .NET CLR 1.1.4322)';

            curl_setopt($curl,CURLOPT_URL,$url);  //The URL to fetch. This can also be set when initializing a session with curl_init().
            if ( $num_post > 0 && !empty($post) ) {
                curl_setopt($curl,CURLOPT_POST,5);
                curl_setopt($curl,CURLOPT_POSTFIELDS,$post);
            }

            curl_setopt($curl,CURLOPT_RETURNTRANSFER,TRUE);	//TRUE to return the transfer as a string of the return value of curl_exec() instead of outputting it out directly.
            curl_setopt($curl,CURLOPT_CONNECTTIMEOUT,5);	//The number of seconds to wait while trying to connect.

            curl_setopt($curl, CURLOPT_USERAGENT, $userAgent);	//The contents of the "User-Agent: " header to be used in a HTTP request.
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE);	//To follow any "Location: " header that the server sends as part of the HTTP header.
            curl_setopt($curl, CURLOPT_AUTOREFERER, TRUE);	//To automatically set the Referer: field in requests where it follows a Location: redirect.
            curl_setopt($curl, CURLOPT_TIMEOUT, 15);	//The maximum number of seconds to allow cURL functions to execute.
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);	//To stop cURL from verifying the peer's certificate.
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);

            $contents = curl_exec($curl);
            curl_close($curl);
            return $contents;
        }

    }

?>
