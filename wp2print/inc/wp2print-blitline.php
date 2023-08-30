<?php

class Blitline {
    public $key;
    public $url = "http://api.blitline.com/job";
    public $response;

    public function __construct($key) {
        $this->key = $key;
    }

    /**
     * @param $src
     * @param $function
     * @param $params
     * @return bool
     */
    public function job($src, $fkey) {
	    $s3bucket = 'pwthumbnail';
		$s3folder = 'wp2print';

		$mtime = explode(' ', microtime());

		$extension = '.'.array_pop(explode('.', $src));
		$identifier = 'image_'.$fkey.md5($src);

		if (strpos($extension, '?')) { $extension = substr($extension, 0, strpos($extension, '?')); }

		$extension = strtolower($extension);

        $package = array();
        $package['application_id'] = $this->key;
        $package['src'] = $src;
        $package['v'] = 1.21;

		$multi_page = false;
		if ($extension == '.pdf') {
			$multi_page = true;
			$extension = '.jpg';
			$package['src_type'] = 'multi_page';
		}

		$package['functions'] = array(
			array(
				'name' => 'resize_to_fit',
				'params' => array(
					'width' => 800,
					'height' => 600,
				),
				'save' => array(
					'image_identifier' => $s3bucket.'/'.$s3folder.'/'.$identifier,
					's3_destination' => array('bucket' => $s3bucket.'/'.$s3folder, 'key' => $identifier . $extension)
				)
			)
		);

        if ($extension == ".png") {
            $package['functions'][0]['save']['png_quantize'] = true;
        }

        if ($this->request(array('json' => json_encode($package)))) {

            $result = json_decode($this->response, true);

            if (count(@$result['results']['images'])) {
				$image_url = array_pop($result['results']['images'])['s3_url'];
				if (strpos($image_url, 's3.amazonaws.com'.'/'.$s3bucket)) {
					$image_url = str_replace('/'.$s3bucket, '', $image_url);
					$image_url = str_replace('s3.amazonaws.com', $s3bucket.'.s3.amazonaws.com', $image_url);
				}
				$image_url = str_replace('http:', 'https:', $image_url);
				if ($multi_page) {
					$image_url = str_replace($extension, '_0'.$extension, $image_url);
				}
                return $image_url;
            } else {
                return false;
            }

        } else {
            echo "Something went wrong with the send.";
        }


    }

    private function request($data) {
        $url = $this->url;

        //init the curl request
        $ch = curl_init();
        $qry_str = http_build_query($data);

        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $qry_str);


        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, '30');

        //send it off & save the result
        $content = trim(curl_exec($ch));
        curl_close($ch);

        if ($content) {
            $this->response = $content;
            return true;
        } else {
            return false;
        }

    }

}