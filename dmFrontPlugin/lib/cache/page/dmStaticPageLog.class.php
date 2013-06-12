<?php

class dmStaticPageLog
{

    const STATIC_LOG_MAX_SIZE = 10485760; // 10 MB

    public static function log($env = 'prod')
    {

        $browser = self::getBrowser();
        $data = array(
            'time' => $_SERVER['REQUEST_TIME'],
            'uri' => substr(self::cleanUri($_SERVER['REQUEST_URI']), 0, 500),
            'code' => 200,
            'env' => $env,
            'ip' => $_SERVER['REMOTE_ADDR'],
            'user_agent' => substr($_SERVER['HTTP_USER_AGENT'], 0, 500),
            'mem' => memory_get_peak_usage(true),
            'duration' => (microtime(true) - dmPageCacheLoader::getInstance()->start)
        );
        $path = str_replace('diem/dmFrontPlugin/lib/cache/page', '', dirname(__FILE__)) . 'data/dm/log/static.log';
        $fh = fopen($path, 'a');
        fwrite($fh, implode('|', array_merge($data, $browser)) . "\n");
        fclose($fh);
        
        if (filesize($path) > self::STATIC_LOG_MAX_SIZE) {
            file_put_contents($path, 'VIOLENT CLEAN UP OCCURRED!!!' . "\n");
        }
    }
    
    protected static function cleanUri($uri)
    {
        if (strpos($uri, '?_=')) {
            $cleanUri = preg_replace('|(.+)(?:\?_=\d+)(.*)|', '$1$2', $uri);

            if ($firstAmp = strpos($cleanUri, '&')) {
                $cleanUri{$firstAmp} = '?';
            }
        } else {
            $cleanUri = $uri;
        }

        return '' === $cleanUri ? '/' : $cleanUri;
    }

    protected static function getBrowser()
    {
        $u_agent = $_SERVER['HTTP_USER_AGENT'];
        $bname = 'Unknown';
        $platform = 'Unknown';
        $version = "";

        //First get the platform?
        if (preg_match('/linux/i', $u_agent)) {
            $platform = 'linux';
        } elseif (preg_match('/macintosh|mac os x/i', $u_agent)) {
            $platform = 'mac';
        } elseif (preg_match('/windows|win32/i', $u_agent)) {
            $platform = 'windows';
        }

        // Next get the name of the useragent yes seperately and for good reason
        if (preg_match('/MSIE/i', $u_agent) && !preg_match('/Opera/i', $u_agent)) {
            $bname = 'Internet Explorer';
            $ub = "MSIE";
        } elseif (preg_match('/Firefox/i', $u_agent)) {
            $bname = 'Mozilla Firefox';
            $ub = "Firefox";
        } elseif (preg_match('/Chrome/i', $u_agent)) {
            $bname = 'Google Chrome';
            $ub = "Chrome";
        } elseif (preg_match('/Safari/i', $u_agent)) {
            $bname = 'Apple Safari';
            $ub = "Safari";
        } elseif (preg_match('/Opera/i', $u_agent)) {
            $bname = 'Opera';
            $ub = "Opera";
        } elseif (preg_match('/Netscape/i', $u_agent)) {
            $bname = 'Netscape';
            $ub = "Netscape";
        }

        // finally get the correct version number
        $known = array('Version', $ub, 'other');
        $pattern = '#(?<browser>' . join('|', $known) .
            ')[/ ]+(?<version>[0-9.|a-zA-Z.]*)#';
        if (!preg_match_all($pattern, $u_agent, $matches)) {
            // we have no matching number just continue
        }

        // see how many we have
        $i = count($matches['browser']);
        if ($i != 1) {
            //we will have two since we are not using 'other' argument yet
            //see if version is before or after the name
            if (strripos($u_agent, "Version") < strripos($u_agent, $ub)) {
                $version = $matches['version'][0];
            } else {
                $version = $matches['version'][1];
            }
        } else {
            $version = $matches['version'][0];
        }

        // check if we have a number
        if ($version == null || $version == "") {
            $version = "?";
        }

        return array(
            'name' => $ub,
            'version' => $version,
            'platform' => $platform,
        );
    }    
}