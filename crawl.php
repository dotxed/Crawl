<?php

/*
 *  http://blog.xploit.me/2012/development/php-beispiel-eines-simplen-web-crawlers-fur-email-adressen.html
 */

/*
 * Changelog 0.1b
 * - removed unnecessary code
 */

class Crawl {

    private $hp;
    private $content;
    private $rlevel;
    private $rmax;
    private $mails;
    private $urls;

    public function __construct($arg1, $arg2, $arg3, $arg4) {
        if(!$this->isCli()) die("Please use php-cli!");
        $this->hp = $arg1;
        $this->rlevel = $arg2;
        $this->rmax = $arg3;
        $this->mails = $arg4;
    }

    public function getContent() {
        if (!function_exists('curl_init')){
            return htmlentities(@file_get_contents($this->hp, false, $this->getContext()), ENT_QUOTES, 'utf-8');
        }
        else {
			return htmlentities($this->url_get_content(), ENT_QUOTES, 'utf-8');
		}
    }

    private function url_get_content() {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->hp);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }


    public  function getEmailArray() {
        $email_pattern_normal="(([-_.\w]+@[a-zA-Z0-9_]+?\.[a-zA-Z0-9]{2,6}))";
        preg_match_all($email_pattern_normal, $this->content, $result_email, PREG_PATTERN_ORDER);
        $unique_emails=$this->array_unique_deep($result_email);
        return $unique_emails;
    }

    public function getURLArray() {
        $url_pattern= '((http:\/\/|https:\/\/|www\.)[a-zA-Z0-9\-\.]{2,}\.([a-zA-Z.]{2,5}))i';
        preg_match_all($url_pattern, $this->content, $result_url, PREG_PATTERN_ORDER);
        $unique_urls=$this->array_unique_deep($result_url[0]);
        $unique_urls=array_unique($this->setURLPrefix($unique_urls));
        return $unique_urls;
    }

    private function getContext() {
        $opts = array(
            'http' => array(
                'method'=>"GET",
                'header'=>"Content-Type: text/html; charset=utf-8"
            )
        );
        return stream_context_create($opts);
    }

    private function setURLPrefix($array) {
        $v=array(); 
		$i=0;
        foreach ($array as $part) {
            if(preg_match('/^(www\.)/', $part)) $v[$i]='http://'.$part;
            else $v[$i]=$part;
            $i++;
        }
        return $v;
    }

    private function isCli() {
        return php_sapi_name()==="cli";
    }

    private function array_unique_deep($array) {
        $values=array();
        foreach ($array as $part) {
            if (is_array($part)) $values=array_merge($values,$this->array_unique_deep($part));
            else $values[]=$part;
        }
        return array_unique($values);
    }

    public  function start() {
        if($this->rlevel<$this->rmax) {
            $this->content = $this->getContent();
            $this->urls = $this->getURLArray();
            $mails = $this->getEmailArray();
            foreach($this->urls as $url) {
               $temp = new Crawl($url, $this->rlevel+1, $this->rmax, $this->mails);
               $this->mails = array_unique(array_merge($temp->start(), $mails));
            }

        }
        return $this->mails;
    }

}

    echo "==================================================\r\n";
    echo " Welcome to Crawl 0.1 -THIS SHIT WORKS AS INTENDED\r\n";
    echo "==================================================\r\n";
    echo " Make sure cURL is activated for best perfomance  \r\n";
	echo "==================================================\r\n";
	if(!isset($argv[2])) {
			echo " Usage: ".$argv[0]." HOST r_Level\r\n";
			echo " Example: ".$argv[0]." http://wikipedia.de 3\r\n";
			echo "==================================================\r\n";
			die();
	}
    else {
		echo " Working... time depends on recursion level\r\n";
		echo "==================================================\r\n";
		$start = new Crawl($argv[1], 0, $argv[2], array());
		var_dump($start->start());
		die();
	}

?>