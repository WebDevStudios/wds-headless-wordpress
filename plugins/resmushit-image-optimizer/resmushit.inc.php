<?php

require('resmushit.settings.php');
require('classes/resmushit.class.php');
require('classes/resmushitUI.class.php');
require('resmushit.admin.php');



/**
* 
* Embedded file log function
*
* @param string $str text to log in file
* @return none
*/
function rlog($str, $level = 'SUCCESS') {
	global $is_cron;

	if(isset($is_cron) && $is_cron) {
		switch ($level) {
			case 'WARNING':
				$prefix = "[\033[33m!\033[0m]"; break;
			case 'ERROR':
				$prefix = "[\033[31m!\033[0m]"; break;
			default:
			case 'SUCCESS':
				$prefix = "[\033[32m+\033[0m]"; break;
		}
		echo "$prefix $str\n";
	}

	if(get_option('resmushit_logs') == 0)
		return FALSE;

	if( !is_writable(ABSPATH) ) {
		return FALSE;
	}
	// Preserve file size under a reasonable value
	if(file_exists(ABSPATH . RESMUSHIT_LOGS_PATH)){
		if(filesize(ABSPATH . RESMUSHIT_LOGS_PATH) > RESMUSHIT_LOGS_MAX_FILESIZE) {
			$logtailed = logtail(ABSPATH . RESMUSHIT_LOGS_PATH, 20);
			$fp = fopen(ABSPATH . RESMUSHIT_LOGS_PATH, 'w');
			fwrite($fp, $logtailed);
			fclose($fp);
		}
	}
	
	$str = "[".date('d-m-Y H:i:s')."] " . $str;
	$str = print_r($str, true) . "\n";
	$fp = fopen(ABSPATH . RESMUSHIT_LOGS_PATH, 'a+');
	fwrite($fp, $str);
	fclose($fp);
}


/**
* 
* Tail function for files
*
* @param string $filepath path of the file to tail
* @param string $lines number of lines to keep
* @param string $adaptative will preserve line memory
* @return tailed file
* @author Torleif Berger, Lorenzo Stanco
* @link http://stackoverflow.com/a/15025877/995958
* @license http://creativecommons.org/licenses/by/3.0/
*/
function logtail($filepath, $lines = 1, $adaptive = true) {
	
	$f = @fopen($filepath, "rb");
	if ($f === false) return false;
	if (!$adaptive) $buffer = 4096;
	else $buffer = ($lines < 2 ? 64 : ($lines < 10 ? 512 : 4096));
	fseek($f, -1, SEEK_END);
	if (fread($f, 1) != "\n") $lines -= 1;
	
	$output = '';
	$chunk = '';

	while (ftell($f) > 0 && $lines >= 0) {
		$seek = min(ftell($f), $buffer);
		fseek($f, -$seek, SEEK_CUR);
		$output = ($chunk = fread($f, $seek)) . $output;
		fseek($f, -mb_strlen($chunk, '8bit'), SEEK_CUR);
		$lines -= substr_count($chunk, "\n");
	}

	while ($lines++ < 0) {
		$output = substr($output, strpos($output, "\n") + 1);
	}
	fclose($f);
	return trim($output);
}


/**
* 
* Calculates time ago
*
* @param string $datetime time input
* @param boolean $full number of lines to keep
* @param string $adaptative will preserve line memory
* @return string
* @author Glavić
* @link https://stackoverflow.com/questions/1416697/converting-timestamp-to-time-ago-in-php-e-g-1-day-ago-2-days-ago
*/
function time_elapsed_string($duration, $full = false) {
	$datetime = "@" . (time() - $duration);

    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => __('year', 'resmushit-image-optimizer'),
        'm' => __('month', 'resmushit-image-optimizer'),
        'w' => __('week', 'resmushit-image-optimizer'),
        'd' => __('day', 'resmushit-image-optimizer'),
        'h' => __('hour', 'resmushit-image-optimizer'),
        'i' => __('minute', 'resmushit-image-optimizer'),
        's' => __('second', 'resmushit-image-optimizer'),
    );
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) : __('just now', 'resmushit-image-optimizer');
}


/**
* 
* Find recursively files based on pattern
*
* @param string $pattern file search
* @param boolean $flags 
* @return array
* @author Mike
* @link https://www.php.net/manual/en/function.glob.php#106595
*/
function glob_recursive($pattern, $flags = 0) {
    $files = glob($pattern, $flags);
   
    foreach (glob(dirname($pattern).'/*', GLOB_ONLYDIR|GLOB_NOSORT) as $dir)
    {
        $files = array_merge($files, glob_recursive($dir.'/'.basename($pattern), $flags));
    }
   
    return $files;
}
