<?php

namespace Gerrie\DataService;

class HTTP extends Base {

	/**
	 * Constructor
	 *
	 * @param \Buzz\Browser $connector
	 * @param array $config
	 * @return void
	 */
	public function __construct(\Buzz\Browser $connector, array $config) {
		$this->setConnector($connector);
		$this->setConfig($config);
	}

	/**
	 * Gets the base url for all HTTP requests.
	 *
	 * @param bool $withAuthentication If true, the authentification string will be appended. False otherwise
	 * @return string
	 */
	protected function getBaseUrl($withAuthentication = false) {
		$config = $this->getConfig();
		$baseUrl = rtrim($config['Host'], '/') . '/';

		if ($withAuthentication === true && $this->getConnector()->getListener() instanceof \Buzz\Listener\BasicAuthListener) {
			$baseUrl .= 'a/';
		}

		return $baseUrl;
	}

	/**
	 * Verifies the last request.
	 * If the last request was not successful, it will be throw an exception.
	 *
	 * @param \Buzz\Message\Response $response The response object from the last reques
	 * @param string $url The url which was requested
	 * @return \Buzz\Message\Response
	 * @throws \Exception
	 */
	protected function verifyResult(\Buzz\Message\Response $response, $url) {
		if ($response->getStatusCode() !== 200) {
			throw new \Exception('Request to "' . $url . '" failed', 1364061673);
		}

		return $response;
	}

	/**
	 * Transforms a JSON string into an array.
	 * Regular, the json is the content from the response.
	 *
	 * @param string $json The json string
	 * @return array|null
	 */
	public function transformJsonResponse($json) {
		// In a REST-API call, the first five chars are )]}'\n
		// to decode it, we have to strip it
		// See https://review.typo3.org/Documentation/rest-api.html#output
		if (substr($json, 0, 4) === ')]}\'') {
			$json = substr($json, 5);
		}

		return json_decode($json, true);
	}

	/**
	 * Initiales the query limit
	 *
	 * @return int
	 */
	protected function initQueryLimit() {
		$url = $this->getBaseUrl(true) . 'accounts/self/capabilities?format=JSON';
		$response = $this->getConnector()->get($url);
		$response = $this->verifyResult($response, $url);

		$content = $this->transformJsonResponse($response->getContent());

		return $content['queryLimit']['max'];
	}

	/**
	 * Gets the Host
	 *
	 * @return string
	 */
	public function getHost() {
		$config = $this->getConfig();
		$host = parse_url($config['Host'], PHP_URL_HOST);

		return $host;
	}

	/**
	 * Requests projects at the Gerrit server
	 *
	 * @return array|null
	 */
	public function getProjects() {
		$urlParts = array(
			'format' => 'JSON',
			'description' => '',
			'type' => 'all',
			'all' => '',
			'tree' => '',
		);

		$url = $this->getBaseUrl() . 'projects/?' . http_build_query($urlParts);
		$response = $this->getConnector()->get($url);
		$response = $this->verifyResult($response, $url);

		$content = $this->transformJsonResponse($response->getContent());

		return $content;
	}

	/**
	 * Requests changesets at the Gerrit server.
	 *
	 * This method is not implemented yet, because at the moment (2013-03-24) Gerrit 2.6.* is not released.
	 * Many Gerrit systems (e.g. TYPO3, WikiMedia, OpenStack, etc.) are running at 2.5.*.
	 * In 2.5.* the SSH API delivers more information than the REST API.
	 *
	 * If Gerrit 2.6 is released, the HTTP DataService will be extended and fully implemented.
	 * Maybe, you want to help me?
	 *
	 * @param string $projectName The project name
	 * @param string $resumeKey The key where the request will be resumed
	 * @throws \Exception
	 */
	public function getChangesets($projectName, $resumeKey = null) {
		throw new \Exception('Not implemented yet', 1364127762);
		// /usr/bin/ssh -p 29418 review.typo3.org gerrit query --format 'JSON' --current-patch-set  --all-approvals --files --comments --commit-message --dependencies --submit-records 'project:Documentation/ApiTypo3Org' limit:'500' 2>&1"
		// /usr/bin/ssh -p 29418 review.typo3.org gerrit query --format 'JSON' --current-patch-set  --all-approvals --files --comments --commit-message --dependencies --submit-records 'project:Documentation/ApiTypo3Org' limit:'500' resume_sortkey:'00215ec7000041b3' 2>&1
	}
}