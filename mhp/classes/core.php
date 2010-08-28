<?php

/**
 * Core class
 */
class Core {

	protected
		$config,
		$startTime;

	public
		$crumb,
		$errors,
		$page,
		$pages,
		$tokens;

	/**
	 * Core constructor
	 */
    function __construct() {

		$this->crumb['home'] = '/';
		$this->errors = Array();
		$this->startTime = microtime(true);
		$this->setTokens();

    }

	/**
	 * Add a breadcrumb
	 * @param string $name The text label the user will see
	 * @param string $url The URL/href attached to the anchor
	 */
	function addCrumb($name, $url) {
		$this->crumb[$name] = $url;
	}

	/**
	 * Execute the model-view-controller emulation (model's not really there, d'oh)
	 * @member MHP
	 * @public
	 */
	function execMVC() {

		$page = $this->tokens[0];

		if ($page == '') {
			$page = 'home';
		}

		if (in_array($page, $this->pages)) {

			$this->setPage($page);

			$new_page = $this->execPage($page);

			if ($new_page) {
				$page = $new_page;
			}

		} else {
			$page = $this->setError(404);
		}

		$this->showComplete($page);

	}

	/**
	 * Search for a page and execute it
	 * @param string $page The page to execute
	 */
	function execPage($page) {

		$file = $this->config['Path']['Exec_Local'] . $page . '.php';

		if (is_file($file)) {
			return require($file);
		} else {
			return 'error';
		}

	}

	/**
	 * Gets the time since startTime was set (in milliseconds)
	 * @return float The milliseconds since starting the script
	 */
	function getGenerationTime() {
		return (microtime(true) - $this->startTime);
	}

	/**
	 * Gets a page number from the tokens array
	 * @param int $slice The designated slice number to check (default is to check the last one)
	 */
	function getPageNumber($slice = null) {

		if ($slice == null) {
			$slice = $slice = (count($this->tokens) - 1);
		}

		$check = $this->tokens[$slice];

		if (substr($check, 0, 4) == 'page') {
			$page = (int) substr($check, 4, strlen($check));
		} else {
			$page = 1;
		}

		if ($page < 1) {
			$page = 1;
		}

		return $page;

	}

	/**
	 * Prepare to generate the error page
	 * @param string $error The error code to set
	 */
	function setError($error) {

		if ($error == 404) {
			header('HTTP/1.0 404 Not Found');
		}

		$this->error = $error;
		$this->page_title = 'Error';

		return 'error';

	}

	/**
	 * Set the page to the passed parameter
	 * @param string $page The page to set to
	 */
	function setPage($page) {
		$this->page = $page;
	}

	/**
	 * Create references to the tokens and fixes inherent problems
	 */
	private function setTokens() {

		$this->tokens = explode('/', $_SERVER['REQUEST_URI']);

		// Take out the first (empty) result
		array_shift($this->tokens);

		// Remove GET vars from the last token
		$str = &$this->tokens[count($this->tokens) - 1];
		$pos = strpos($str, '?');

		if ($pos !== false) {
			$str = substr($str, 0, $pos);
		}

	}

	/**
	 * Sets up plugins by including them
	 */
	function setupPlugins() {

		$plugins = Array(
			'Chats',
			'Comments'
		);

		foreach ($plugins as $plugin) {
			if ($this->config[$plugin]) {
				$lower = strtolower($plugin);
				require($this->config['Path']['MHP_Local'] . 'classes/' . $lower . '.php');
				$this->$lower = new $plugin($this->config);
			}
		}

	}

	/**
	 * Show a complete HTML document
	 * @param string $page The page to show
	 * @member MHP
	 * @public
	 */
	function showComplete($page) {

		header('Content-Type: text/html; charset=utf-8');

		$this->showPage('header');
		$this->showPage($page);
		$this->showPage('footer');

		exit;

	}

	/**
	 * Search for a page and display it
	 * @param string $page The page to display
	 */
	function showPage($page) {

		$file = $this->config['Path']['Pages_Local'] . $page . '.php';

		if (is_file($file)) {
			require($file);
		} else {
			echo "<!-- Couldn't find $page -->\n";
		}

	}

}
