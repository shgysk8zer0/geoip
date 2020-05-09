<?php
namespace shgysk8zer0\GeoIP;

use \Exception;
use \JsonSerializable;

final class GeoIPException extends Exception implements JsonSerializable
{
	private $_type = 'unknown';

	final public function __construct(string $message, int $code = 0, ?string $type = 'unknown')
	{
		parent::__construct($message, $code);
		$this->_type = $type;
	}

	final public function jsonSerialize(): array
	{
		return [
			'message' => $this->getMessage(),
			'type'    => $this->getType(),
			'code'    => $this->getCode(),
		];
	}

	final public function getType():? string
	{
		return $this->_type;
	}

	final public function __debugInfo(): array
	{
		return [
			'message' => $this->getMessage(),
			'code'    => $this->getCode(),
			'type'    => $this->getType(),
			'file'    => $this->getFile(),
			'line'    => $this->getLine(),
			'trace'   => $this->getTrace(),
		];
	}
}
