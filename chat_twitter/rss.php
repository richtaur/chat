<?php

class RSS {

	private
		$json = null;

	public function __construct($url) {
		$this->set($url);
	}

	public function get() {
		return $this->json;
	}

	private function set($url) {

		$contents = file_get_contents($url);

		if ($contents) {

			$xml = @new SimpleXMLElement($contents);

			if ($xml) {

				$items = $xml->xpath('/rss/channel/item');

				if ($items) {
					$this->json = json_encode($items[0]);
				} else {
					$entry = $xml->entry[0];
					$this->json = json_encode($entry);
				}

			}

		}

	}

}
