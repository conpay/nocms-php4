<?php
class ConpayProxyModelPhp4
{
	/**
	 * @var int
	 */
	var $merchantId;
	/**
	 * @var string
	 */
	var $serviceUrl = 'https://www.conpay.ru/service/proxy';
	/**
	 * @var string
	 */
	var $serviceAction;
	/**
	 * @var string
	 */
	var $charset = 'WINDOWS-1251';
	/**
	 * @var string
	 */
	var $conpayCharset = 'UTF-8';

	/**
	 * @constructor
	 */
	function ConpayProxyModelPhp4()
	{
		set_error_handler('ConpayProxyModelPhp4::errorHandler', E_USER_ERROR);

		if (!$this->isSelfRequest()) {
			trigger_error('Incorrect request', E_USER_ERROR);
		}

		$this->serviceAction = isset($_POST['conpay-action']) ? $_POST['conpay-action'] : '';
		$this->serviceUrl = rtrim($this->serviceUrl.'/'.$this->serviceAction, '/');
	}

	/**
	 * @return boolean
	 */
	function isSelfRequest() {
		return isset($_SERVER['HTTP_REFERER']) && parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST) === $_SERVER['HTTP_HOST'];
	}

	/**
	 * @return boolean
	 */
	function isPostRequest() {
		return isset($_SERVER['REQUEST_METHOD']) && !strcasecmp($_SERVER['REQUEST_METHOD'], 'POST');
	}

	/**
	 * @return string
	 */
	function sendRequest()
	{
		$response = function_exists('curl_init') ? $this->_getViaCurl() : $this->_getViaFileGC();
		return $this->_convertCharset($this->conpayCharset, $this->charset, $response);
	}

	/**
	 * @param int $id
	 * @return ConpayProxyModelPhp4
	 */
	function setMerchantId($id) {
		$this->merchantId = (int)$id;
	}

	/**
	 * @param string $charset
	 * @return ConpayProxyModelPhp4
	 */
	function setCharset($charset) {
		$this->charset = strtoupper($charset);
	}

	/**
	 * @return string
	 */
	function _getViaCurl()
	{
		$ch = curl_init($this->serviceUrl);

		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 5.1; rv:9.0.1) Gecko/20100101 Firefox/9.0.1');
		curl_setopt($ch, CURLOPT_REFERER, $_SERVER['HTTP_REFERER']);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $this->_getQueryData());
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

		$data = curl_exec($ch);

		if ($data === false)
		{
			$error = curl_error($ch);
			curl_close($ch);
			trigger_error($this->_convertCharset($this->conpayCharset, $this->charset, $error), E_USER_ERROR);
		}

		curl_close($ch);
		return $data;
	}

	/**
	 * @return string
	 */
	function _getViaFileGC()
	{
		$options = array(
			'http'=>array(
				'method'=>"POST",
				'content'=>$this->_getQueryData(),
				'header'=>
					"Content-type: application/x-www-form-urlencoded\r\n".
					"Referer: {$_SERVER['HTTP_REFERER']}\r\n"
			)
		);

		$context = stream_context_create($options);
		return file_get_contents($this->serviceUrl, false, $context);
	}

	/**
	 * @return string
	 */
	function _getQueryData()
	{
		if ($this->merchantId === null) {
			trigger_error('MerchantId is not set', E_USER_ERROR);
		}
		$data = $this->isPostRequest() ? http_build_query($_POST) : $_SERVER['QUERY_STRING'];
		if (strpos($data, 'merchant=') === false) {
			$data .= '&merchant='.$this->merchantId;
		}
		return $this->_convertCharset($this->charset, $this->conpayCharset, $data);
	}

	/**
	 * @param string $in
	 * @param string $out
	 * @param string $data
	 * @return string
	 */
	function _convertCharset($in, $out, $data)
	{
		if ($in !== $out && function_exists('iconv')) {
			return iconv($in, $out, $data);
		}
		return $data;
	}

	/**
	 * @param int $errno
	 * @param string $errstr
	 */
	static function errorHandler($errno, $errstr)
	{
		if ($errno === E_USER_ERROR)
		{
			json_encode(array('error'=>$errstr));
			exit();
		}
	}
}