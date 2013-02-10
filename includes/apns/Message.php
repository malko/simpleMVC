<?php

class apnsMessageException extends Exception{}
class apnsMessageOverflowException extends apnsMessageException{}

class apnsMessage{

	static private $nextId=0;

	protected $ttl = 604800; // set default time to live of the message to One week
	protected $payload=null;

	private $preparedBinaryString = null;

	function __construct($payload=null){
		$this->setPayload($payload);
	}

	function __get($k){
		if( isset($this->$k) ){
			return $this->$k;
		}
	}

	function __call($m,$a){
		if( preg_match('!^set(.*)$!',$m,$match)){
			$prop = strtolower(substr($match[1],0,1)).substr($match[1],1);
			if( property_exists($this,$prop) ){
				$this->{$prop} = $a[0];
				if( $prop === 'payload' && mb_strlen($a[0],'latin1') > 256){
					throw new apnsMessageOverflowException("apnsMessage::$m() Payload can't exceed 256bytes");
				}
				if( in_array($prop,array('ttl','payload'),true) ){
					$this->_prepareBinary();
				}
				return $this;
			}
		}
		throw new apnsMessageException("apnsMessage::$m() Method doesn't exists");
	}

	protected function _prepareBinary(){
		$this->preparedBinaryString =
			pack('C',1)                                         // command 1 for enhanced message
			.'---ID---'                                         // message identifier for eventual error response
			.pack('N',$this->ttl > 0 ? time() + $this->ttl : 0) // Expiry
			.pack('n',32)                                       // device token length
			.'--DEVICETOKEN--'                                  // will be replaced by pack('H*',$deviceToken)
			.pack('n',mb_strlen($this->payload, 'latin1'))
			.$this->payload
		;
	}

	function getBinary($deviceToken,$id=null){
		if( null === $id ){
			$id = self::$nextId++;
			if( self::$nextId > 2000000000 ){ //reset messages ids before exceedings 32 bits
				self::$nextId = 0;
			}
		}
		return str_replace(
			array('--DEVICETOKEN--','---ID---')
			,array(pack('H*',$deviceToken),pack('N',$id))
			,$this->preparedBinaryString
		);
	}

	function sendTo(apnsService $service, $deviceToken,$id=null){
		return $service->send($this->getBinary($deviceToken,$id));
	}
}
