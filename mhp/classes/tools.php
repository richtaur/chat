<?php

/**
 * Tools class
 */
class Tools {

	const
		SECONDS_ONE_DAY = 86400,
		SECONDS_ONE_WEEK = 604800,
		SECONDS_ONE_MONTH = 2629743;

	/**
	 * Checks an associatve array to see if $field matches $value, and returns false or the index
	 * @param array haystacks The associative array to search through
	 * @param string value The value to search in the array for
	 * @param string field The fieldname to look in
	 * @return integer index Returns the numeric index if found, or false on failure (note the different between 0 and false)
	 * @member Tools
	 * @public
	 */
	static function checkAssociative($haystacks, $value, $field) {

		foreach ($haystacks as $index => $haystack) {
			if ($haystack[$field] == $value) {
				return $index;
			}
		}

		return false;

	}

    /**
	 * Perform an imperfect regex to validate an email
	 * @param string email The email to check
	 * @return bool Whether it's a valid email or not
	 * @member Tools
	 * @public
     */
    static function checkEmail($email) {

		$regex = '/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}/i';

		return preg_match($regex, $email);

    }

	public static function checkFile($file) {

		return (
			$file
			&& is_array($file)
			&& $file['name']
			&& ($file['error'] == 0)
			&& $file['size']
		);

	}

	/**
	 * Check if a URL is valid
	 * @param string url The URL to check
	 * @return bool Whether the URL was valid or not
	 * @member Tools
	 * @public
	 */
	static function checkURL($url) {

		$regex = '~(ftp|http|https)://([-\w\.]+)+(:\d+)?(/([\w/_\.]*(\?\S+)?)?)~';

		return preg_match($regex, $url);

	}

	/**
	 * Get the current IP address
	 * @return string The current IP address
	 * @member Tools
	 * @public
	 */
	static function getIP() {
        return $_SERVER['REMOTE_ADDR'];
	}

	/**
	 * Check the passed string for clickable links and totally make that happen
	 * @param string txt The text to check
	 * @return string The same text, only with anchors for the URLs and emails
	 * @member Tools
	 * @public
	 */
	static function formatClickable($txt) {

		$txt = preg_replace('#(script|about|applet|activex|chrome):#is', "\\1:", $txt);

		// pad it with a space so we can match things at the start of the 1st line.
		$ret = ' ' . $txt;

		// matches an "xxxx://yyyy" URL at the start of a line, or after a space.
		// xxxx can only be alpha characters.
		// yyyy is anything up to the first space, newline, comma, double quote or <
		$ret = preg_replace("#(^|[\n ])([\w]+?://[\w\#$%&~/.\-;:=,?@\[\]+]*)#is", "\\1<a href=\"\\2\" rel=\"nofollow\" target=\"_blank\">\\2</a>", $ret);

		// matches a "www|ftp.xxxx.yyyy[/zzzz]" kinda lazy URL thing
		// Must contain at least 2 dots. xxxx contains either alphanum, or "-"
		// zzzz is optional.. will contain everything up to the first space, newline,
		// comma, double quote or <.
		$ret = preg_replace("#(^|[\n ])((www|ftp)\.[\w\#$%&~/.\-;:=,?@\[\]+]*)#is", "\\1<a href=\"http://\\2\" rel=\"nofollow\" target=\"_blank\">\\2</a>", $ret);

		// matches an email@domain type address at the start of a line, or after a space.
		// Note: Only the followed chars are valid; alphanums, "-", "_" and or ".".
		$ret = preg_replace("#(^|[\n ])([a-z0-9&\-_.]+?)@([\w\-]+\.([\w\-\.]+\.)*[\w]+)#i", "\\1<a href=\"mailto:\\2@\\3\">\\2@\\3</a>", $ret);

		// Twitter usernames
		$ret = preg_replace('#@([a-z0-9_]*)#i', '@<a href="http://twitter.com/\\1" target="_blank">\\1</a>', $ret);

		// Remove our padding..
		$ret = substr($ret, 1);

		return $ret;

	}

	/**
	 * Convert every character from the passed string into its corresponding HTML entity
	 * @param string text The string to convert
	 * @return string The resulting HTML entities
	 * @member Tools
	 * @public
	 */
    static function formatHTMLEntities($text) {

        $len = strlen($text);
        $range_begin = 48;  // 0
        $range_end   = 122; // z

        for ($i = 0; $i <= $len; $i++) {

            $ord_val = ord($text{$i});
            $char    = '&#' . $ord_val . ';';
            $html   .= ((($ord_val >= $range_begin) && ($ord_val <= $range_end)) ? $char : $text{$i});

        }

        return $html;
    
    }

	/**
	 * Formats a link for HTML output
	 * Used by class methods such as formatLinkProfile and formatLinkTopic
	 * @param string url The URL to include in the markup
	 * @param string text The text to include in the anchor [optional]
	 * @param string extra The extra nodes to include in the <a> tag [optional]
	 * @return string The URL in HTML format
	 * @member Tools
	 * @public
	 */
	static function formatLink($url, $text = null, $extra = null) {

		if ($text) {
			$url = '<a ' . ($extra ? $extra . ' ' : '') . 'href="' . $url . '">' . $text . '</a>';
		}

		return $url;

	}

	/**
	 * Truncates text longer than the length specified
	 * @param string text The text to truncate
	 * @param integer length The maximum length to truncate after
	 * @param string suffix What to append to the string if it's too long
	 * @member Tools
	 * @public
	 */
	static function formatLongText($text, $length, $suffix = '...') {

		if (strlen($text) > $length) {
			$text = substr($text, 0, ($length - strlen($suffix))) . $suffix;
		}

		return $text;

	}

	/**
	 * Checks a number to see if its accompanying string needs an "s" for multiples
	 * (Note that if the num parameter is an array, the number becomes a count of that array)
	 * @param integer|array num The number of items
	 * @param string text The text to return, with or without an "s"
	 * @param bool include_number Whether to include the number or not
	 * @return string The returned text
	 * @member Tools
	 * @public
	 */
	static function formatMultiple($num, $text, $include_number = true) {

		if (is_array($num)) {
			$num = count($num);
		}

		$num = number_format($num);

		return ($include_number ? "$num " : '') . $text . (($num == 1) ? '' : 's');

	}

	/**
	 * Converts a (comma) separated string into an array
	 * @param string text The separated string to chop up
	 * @param string format The format to return [array, string]
	 * @param string wrap The string to wrap around the output, eg 'item 1','item2' [optional]
	 * @param string sep The separator to break based on [default: ,]
	 * @param array The array of chopped up strings
	 * @member Tools
	 * @public
	 */
	static function formatSeparated($text, $format = 'string', $wrap = null, $sep = ',') {

		$commas = explode($sep, $text);

		foreach ($commas as $key => $value) {
			$res[$key] = ($wrap ? $wrap : '') . trim($value) . ($wrap ? $wrap : '');
		}

		if ($format == 'string') {

			foreach ($res as $value) {
				$str .= $value . ',';
			}

			$res = substr($str, 0, -1);

		}

		return $res;

	}

	/**
	 * Convert seconds to minutes and seconds
	 * @param int $seconds The number of seconds to format
	 * @return string The formatted minutes and seconds
	 * @member Tools
	 * @public
	 */
	static function formatSecondsToMinutes($seconds) {

		$minutes = floor($seconds / 60);
		$seconds = $seconds - ($minutes * 60);

		if ($seconds < 10) {
			$seconds = '0' . $seconds;
		}

		return $minutes . ':' . $seconds;

	}

	/*
	 * Formats a string into a URL-friendly and SEO'd string
	 * @param string text The text to convert
	 * @param mixed slug_chars The allowed characters; can be an array or a string
	 * @param string slug_space The character to replace spaces with
	 * @return string The formatted string
	 * @member Tools
	 * @public
	 */
	static function formatSlug($text, $slug_chars = null, $slug_space = '-') {

		if ($slug_chars === null) {
			$slug_chars = self::makeAlphaNumericArray();
		}

		// We need an array to work with
		if (is_string($slug_chars)) {
			$slug_chars = str_split($slug_chars);
		}

		if (!$slug_chars) {
			return;
		}

		$text = strtolower($text);
		$text = trim($text);

		// Loop through the string and generate the slug
		for ($i = 0; $i < strlen($text); $i++) {

			$char_add = '';
			$char_check = $text{$i};

			// If this is in the array, it must be added
			if (in_array($char_check, $slug_chars)) {
				$char_add = $char_check;
			// If this is a space and it's not in a series, it must be added
			} else if (($char_check == ' ') && ($char_last != $slug_space)) {
				$char_add = $slug_space;
			}

			if ($char_add) {
				$char_last = $char_add;
			}

			$slug .= $char_add;

		}

		return $slug;

	}

	/**
	 * Format the different between two timestamps
	 * @param int levels The number of levels to go [1: "1 day", 2: "1 day, 2 hours", 3: "1 day, 2 hours, 36 minutes"]
	 * @param int start The start timestamp
	 * @param int stop The ending timestamp
	 * @return string The string in human-readable format, eg "2 weeks"
	 * @member Tools
	 * @public
	 */
	static function formatTimeDiff($levels, $start, $stop = null) {

		// Default to the current time
		if ($stop == null) {
			$stop = time();
		}

		// Don't do anything if there's no time between them
		if ($start == $stop) {
			return 'just now';
		}

		// Find out which one's first
		if ($start > $stop) {
			$seconds = ($start - $stop);
		} else {
			$seconds = ($stop - $start);
		}

		// Set this uip for easily readable math
		$one_minute = 60;
		$one_hour = ($one_minute * 60);
		$one_day = ($one_hour * 24);
		$one_week = ($one_day * 7);
		$one_month = ($one_day * 30);
		$one_year = ($one_day * 365);

		// Loop through and generate the levels
		for ($i = 0; $i < $levels; $i++) {

			// Decide what we've got here
			$minutes = floor($seconds / 60);
			$hours = floor($minutes / 60);
			$days = floor($hours / 24);
			$weeks = floor($days / 7);
			$months = floor($days / 30);
			$years = floor($days / 365);

			// Go down the levels to see what we should show
			if ($years) {
				$res = self::formatMultiple($years, 'year');
				$seconds -= ($years * $one_year);
			} else if ($months) {
				$res = self::formatMultiple($months, 'month');
				$seconds -= ($months * $one_month);
			} else if ($weeks) {
				$res = self::formatMultiple($weeks, 'week');
				$seconds -= ($weeks * $one_week);
			} else if ($days) {
				$res = self::formatMultiple($days, 'day');
				$seconds -= ($days * $one_day);
			} else if ($hours) {
				$res = self::formatMultiple($hours, 'hour');
				$seconds -= ($hours * $one_hour);
			} else if ($minutes) {
				$res = self::formatMultiple($minutes, 'minute');
				$seconds -= ($minutes * $one_minute);
			} else if ($seconds || ($i == 0)) {
				$res = self::formatMultiple($seconds, 'second');
				$levels = 0;
			} else {
				$res = '';
			}

			if ($res) {
				if ($diff) {
					$diff .= ", $res";
				} else {
					$diff = $res;
				}
			}

		}

		return "$diff ago";

	}

	/**
	 * Ensure the passed URL has a sensical protocol
	 * @param string url The URL to check
	 * @param string default The protocol to set if none was found (default is HTTP)
	 * @member Tools
	 * @public
	 */
    static function formatURL($url, $default = 'http://') {

		#$url = urlencode($url);
		$url = self::formatValidEntities($url);

        $protocols = Array(
            '/',
            'ftp://',
            'http://',
            'https://',
            'mailto:'
        );

        // Loop through the valid protocols. If one is found, return the string as-is
        foreach ($protocols as $protocol) {
            if (strtolower(substr($url, 0, strlen($protocol))) == $protocol) {
                return $url;
            }
        }

        return ($default . $url);

    }

	/**
	 * Formats invalid entites (&) into valid entities (&amp;)
	 * @param string text The text to convert
	 * @return string The formatted text
	 * @member Tools
	 * @public
	 */
	static function formatValidEntities($text) {

		$pattern = '/&(?![#0-9a-z]+;)/';
		$replace = '&amp;';

		return preg_replace($pattern, $replace, $text);

	}

	/**
	 * Get the extension -- the string left over after the last period (.)
	 * @param string filename The filename to extract the extension from
	 * @member Tools
	 * @public
	 */
    static function getExtension($filename) {

			$ext = explode('.', $filename);

			return array_pop($ext);

    }

	public static function hasImageExtension($filename) {

    $ext = strtolower(self::getExtension($filename));
		$exts = array('bmp', 'gif', 'jpg', 'jpeg', 'png');

		return in_array($ext, $exts);

	}

	/**
	 * Highlights certain parts of a string
	 * @param string $needle The string to look for
	 * @param string $haystack The string to look in
	 * @param string $tag_open The tag to prefix with
	 * @param string $tag_close The tag to use as a suffix
	 * @return string The string with highlights
	 * @member Tools
	 * @public
	 */
	static function highlight($needle, $haystack, $tag_open, $tag_close) {

		if (!$needle) {
			return $haystack;
		}

		$pattern = "/$needle/i";
		$replace = "$tag_open\\0$tag_close";
		$result = preg_replace($pattern, $replace, $haystack);

		if ($result) {
			return $result;
		} else {
			return $haystack;
		}

		return $result;

	}

	/**
	 * Checks if a string is valid based on the passed rules
	 * @param str txt The string to check
	 * @param str rules The rules to follow
	 * @return bool Whether the string is valid or not
	 * @member Tools
	 * @public
	 */
	static function isValidString($txt, $rules) {

		if (is_string($rules)) {
			$rules = str_split($rules);
		}

		for ($i = 0; $i < strlen($txt); $i++) {

			if (!in_array($txt{$i}, $rules)) {
				return false;
			}

		}

		return true;

	}

	/**
	 * Creates a case-insensitive alphanumeric array
	 * @param string extra_chars Any extra characters you'd like to tack onto the alphanumeric array
	 * @return array The array of alphanumeric characters (and additions)
	 * @member Tools
	 * @public
	 */
    static function makeAlphaNumericArray($extra_chars = '') {

		// This new method is probably way faster (it's certainly less lines) but needs to be benchmarked
		$chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789' . $extra_chars;
		$len = strlen($chars);

		for ($i = 0; $i < $len; $i++) {
			$arr[] = $chars{$i};
		}

		return $arr;

    }

	/**
	 * Redirects to the passed URL
	 * @param string url The new location
	 * @member Tools
	 * @public
	 */
    static function redirect($url) {

		if (headers_sent()) {
			echo "\n<!-- Failed redirecting to $url -->\n";
		} else {
			header('Location: ' . $url);
		}

        exit;

    }

	/**
	 * Gets the seconds that have passed since midnight
	 * @return int The number of seconds since midnight
	 * @member Tools
	 * @public
	 */
	static function secondsSinceMidnight() {

		// Get today at midnight
		$secs_midnight = strtotime('today');

		// Return just the seconds since midnight
		return (time() - $secs_midnight);

	}

	/**
	 * Send an email
	 * @param string to The address to send to
	 * @param string subject The subject of the email
	 * @param string message The message of the email
	 * @param string headers Any additional headers such as "From:"
	 * @return bool Whether the message sent successfully or not
	 * @member Tools
	 * @public
	 */
	static function sendEmail($to, $subject, $body, $headers) {
		return mail($to, $subject, $body, $headers);
	}

	/**
     * Strip out the HTML tags < and > and replace them with HTML entities
	 * @param string html The text to strip
	 * @member Tools
	 * @public
	 */
    static function stripHTML($html) {
        return str_replace('>', '&gt;', str_replace('<', '&lt;', $html));
    }

}
