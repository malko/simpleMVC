<?php
/**
* Easy text/HTML Mail with file attachement (possible inline HTML attachement)
* @package MAIL
* @since 2005-08-29
* @licence General Public Licence
* @changelog
*            - 2009-01-28 - better automated html to plain-text conversion
*            - 2008-09-15 - clean_header() now replace words instead of whole string (was problematic with string that contains '?')
*            - 2008-08-26 - add static property $xmailer
*            - 2008-08-25 - better html to plain text transformation when setting body type as both
*            - 2008-03-11 - correct a bug in quoted printable when using mbstring.func_overload with MB_OVERLOAD_STRING and UTF8
*            - 2008-03-07 - clean header will now encode the whole string instead of each special chars separatly
*            - 2008-02-29 - optimisitation in check_adress set regexp only once.
*                         - many code cleaning to work properly with static methods in php5
*            - 2007-09-26 - new parameter cleanTO on send method
*            - 2007-09-18 - add header encoding on clean header
*/

class easymail{

	static public $dfltHeaderCharset = 'UTF-8';
	static public $preferedEncoding  = 'quoted-printable';
	static public $xmailer = "EasyMail0.9.5b";
	#- ~ static public $preferedEncoding  = '7bit';

	function easymail($TO=null,$SUBJECT=null){
		if(!is_null($TO))
			$this->to($TO);
		if(! is_null($SUBJECT) )
			$this->subject($SUBJECT);
		$this->parts_encoding = 'base64';
		self::set_header($this->headers,'MIME-Version','1.0');
		if( self::$xmailer )
			self::set_header($this->headers,'X-Mailer',self::$xmailer);
		$this->boundary = '--main'.md5(uniqid(rand(),true));
	}

	/**
	* remove CR/LF to avoid mail injection.
	* it also encode header when required according to RFC2047 (inspired from htmlMimeMail)
	* @private
	* @param string $headerval the value of the header field
	* @param string $charset  the charset encoding used for the header content (default to $dfltHeaderCharset)
	* @return string
	*/
	static public function clean_header($headerval, $charset){
		if(is_null($charset))
			$charset = self::$dfltHeaderCharset;
		$headerval = preg_replace("![\r\n]!",'',$headerval);
		if( preg_match('/[\x80-\xFF]+/', $headerval) ){
			preg_match_all('/(\w*[\x80-\xFF]+\w*)/', $headerval, $match);
			foreach ($match[1] as $word) {
				$replacement = preg_replace('/([\x80-\xFF])/e', '"=".strtoupper(dechex(ord("\1")))', $word);
				$headerval   = str_replace($word, "=?$charset?Q?$replacement?=", $headerval);
			}
		}
		return $headerval;
	}

	/**
	* simple check of the email validity based on rfc3696 rules
	* @private
	* @param string $email
	* @return string
	*/
	static public function check_address($email){
		static $exp;
		if( ! isset($exp) ){
			$quotable       = '@,"\[\]\\x5c\\x00-\\x20\\x7f-\\xff';
			$local_quoted   = '"(?:[^"]|(?<=\\x5c)"){1,62}"';
			$local_unquoted =  '(?:[^'.$quotable.'\.]|\\x5c(?=['.$quotable.']))'
											 .'(?:[^'.$quotable.'\.]|(?<=\\x5c)['.$quotable.']|\\x5c(?=['.$quotable.'])|\.(?=[^\.])){1,62}'
											 .'(?:[^'.$quotable.'\.]|(?<=\\x5c)['.$quotable.'])';
			$local          = '('.$local_unquoted.'|'.$local_quoted.')';

			$_0_255         = '(?:[0-1]?\d?\d|2[0-4]\d|25[0-5])';
			$domain_ip      = '\['.$_0_255.'(?:\.'.$_0_255.'){3}\]';
			$domain_name    = '(?:[a-z0-9][a-z0-9-]{1,61}[a-z0-9]\.?)+\.[a-z]{2,6}';

			$exp = "!^(?:$local_unquoted|$local_quoted)@(?:$domain_name|$domain_ip)$!";
		}
		return (preg_match($exp,$email)?true:false);
	}
	/**
	* Set the mail subject
	* @param string $SUBJECT the subject
	* @param string $charset header encoding if required (default to $dfltHeaderCharset)
	*/
	function subject($SUBJECT,$charset=null){
		return $this->_subject = self::clean_header($SUBJECT,$charset);
	}
	/**
	* Add 'TO' recipient to the mail
	* @param mixed $TO string or array of recipient(s) mail adress
	*/
	function to($TO){
		if(is_array($TO)){
			foreach($TO as $mail){
				if(easymail::check_address($mail))
					$this->_to[] = $mail;
				else
					return FALSE;
			}
			return TRUE;
		}elseif(easymail::check_address($TO)){
			$this->_to[] = $TO;
			return TRUE;
		}
		return FALSE;
	}
	/**
	* Add 'CC' recipient to the mail
	* @param mixed $CC string or array of recipient(s) mail adress
	*/
	function cc($CC){
		return $this->set_address_header('Cc',$CC);
	}
	/**
	* Add 'BCC' recipient to the mail
	* @param mixed $BCC string or array of recipient(s) mail adress
	*/
	function bcc($BCC){
		return $this->set_address_header('Bcc',$BCC);
	}
	/**
	* Set the FROM adress
	* @param string $FROM
	*/
	function from($FROM){
		if(preg_match('!\s*([^<]+)?<\s*([^@]+@[^>]+)\s*>\s*$!',$FROM,$m)){
			if(! self::check_address($m[2]) )
				return FALSE;
		}elseif(! self::check_address($FROM)){
			return FALSE;
		}
		# if(! isset($this->headers['return-path'])) # ensure return path
			# self::set_header($this->headers,'Return-Path',$FROM);
		return self::set_header($this->headers,'From',$FROM);
	}

	/**
	* set the RETURN-PATH adress
	* @param $RETURN-PATH
	*/
	function return_path($RETURN_PATH){
		if(! self::check_address($RETURN_PATH))
			return FALSE;
		return self::set_header($this->headers,'Return-Path',$RETURN_PATH);
	}

	/**
	* set a mail header containing mail address
	* @param string $field header fieldname
	* @param mixed $adresses header value (string or array)
	* @return bool
	* @private
	*/
	function set_address_header($field,$value){
		if(is_array($value)){
			foreach($value as $mail){
				if(self::check_address($mail))
					self::set_header($this->headers,$field,$mail,TRUE);
				else
					return FALSE;
			}
			return TRUE;
		}elseif(self::check_address($value)){
			return self::set_header($this->headers,$field,$value);
		}
		return FALSE;
 }
	/**
	* set an header field
	* @param array $headers this can be used to passed another array to work on instead $this->headers
	*              (note that the given array will be modified as it is passed by reference)
	* @param string $field header fieldname
	* @param string $value header value
	* @param bool $append if set to true will append to an existing header
	* @return string
	*/
	static public function set_header(&$headers,$field,$value,$append=FALSE,$charset=null){
		$fieldname = strtolower($field);
		if((! isset($headers[$fieldname]) || (! $append) ) )
			$headers[$fieldname] = self::clean_header("$field: $value",$charset);
		else
			$headers[$fieldname] .= "\n\t".self::clean_header($value,$charset);
		return $headers[$fieldname];
	}

	/**
	* Add a file attachment to the mail
	* @param string $file filepath
	* @param bool $inline set to true to attach file as inline part
	* @param string $ctype default is 'application/octet-stream' you can set it to null to force mime_content_type() detection (not recommended but possible)
	* @param string $name set the part name manualluy instead of file basename.
	* @return string part name
	*/
	function attach($file,$inline=FALSE,$ctype='application/octet-stream',$name=null){
		if(! file_exists($file)) return FALSE;
		$datas = array(); # must do this to use anonymous method set_header()
		$datas['name'] = is_null($name)?basename($file):$name;
		self::set_header(
			$datas,'Content-Type',
			((is_null($ctype) && function_exists('mime_content_type'))?mime_content_type($file):$ctype).';'
		);
		self::set_header($datas,'Content-Type',"name=\"$datas[name]\"",TRUE);
		self::set_header($datas,'Content-Transfer-Encoding',$this->parts_encoding,FALSE);
		self::set_header($datas,'Content-Disposition',($inline?'inline':'attachment').';',FALSE);
		self::set_header($datas,'Content-Disposition',"filename=\"$datas[name]\"",TRUE);
		if($inline){
			$datas['cid'] = md5($datas['name'].uniqid(rand()));
			self::set_header($datas,'Content-ID',"<$datas[cid]>",FALSE);
		}
		if(!($f = fopen($file,'r')) )
			return FALSE;
		#@todo correctly handle encoding regarding the parts_encoding property
		$datas['datas'] = chunk_split(base64_encode(fread($f,filesize($file))),76,"\n");
		fclose($f);

		$this->parts[($inline?'inline':'attachment')][] = $datas;
		return $inline?$datas['cid']:$datas['name'];
	}
	/**
	* prepare the part of an attached file for message inclusion
	* @private Be Aware that the part parameter order is important to get this method work!
	* @param array $part array as $datas set in attach
	* @param string $boundary set to an alternate boundary (hmmm not sure that's usefull to you a day)
	* @return string
	*/
	function get_part($part=null,$boundary=null){
		if(! is_array($part))
			return FALSE;
		$datas = $part['datas'];
		unset($part['name'],$part['cid'],$part['datas']);
		return '--'.(is_null($boundary)?$this->boundary:$boundary)."\n".implode("\n",$part)."\n\n$datas\n";
	}
	/**
	* set the message body
	* @param string $body  the message content
	* @param string $ctype the message type plain/html
	* @param bool   $copyasplain set it to true to copy the html part to a plain one
	* @param string $charset
	*/
	function body($body,$ctype='plain',$copyasplain=FALSE,$charset=null){
		if( is_null($charset) )
			$charset = self::$dfltHeaderCharset;
		$ctype = strtolower($ctype);
		$body.="\n";
		if($ctype === 'html'){
			$this->msg_html = array();
			self::set_header($this->msg_html,'Content-Type',"text/html; charset=$charset",FALSE,$charset);
			if(self::$preferedEncoding==='quoted-printable'){
				easymail::set_header($this->msg_html,'Content-Transfer-Encoding','quoted-printable',FALSE,$charset);
				$this->msg_html['datas'] = $this->quoted_printable_encode($body);
			}else{
				self::set_header($this->msg_html,'Content-Transfer-Encoding',self::$preferedEncoding,FALSE,$charset);
				$this->msg_html['datas'] = $body;
			}

			if($copyasplain){
				$ctype = 'plain';
				# replace image with their alt atribute and minimize blank space between tags
				$body  = preg_replace(
					array(
						'!<img[^>]+? alt=([\'"])?((?(1).*?|\S*))(?(1)\1).*?'.'>!si', #- remplace les images par leur balises alt (si présente)
						'!(/?>)\s*(</?\w+)!', #- assure un espace entre chaque balise html
						'!<a[^>]+?href=([\'"])?((?(1).*?(?=\1)|\S*)).*?'.'>(.*?)</a>!sie', # remplace les liens par leur adresses html
					),
					array(
						'\\2',
						'\\1 \\2',
						'("\\3"==="\\2")?"\\2":"\\3 \\2"',
					),
					$body
				);
				$body  = strip_tags($body,'<br><p><tr>');
				# get plain text from body (next 5 lines originaly came from php documentation of html_entity_decode)
				$body   = preg_replace(array('!&#x([0-9a-f]+);!ei','!&#([0-9]+);!e','!  +!'), array('chr(hexdec("\\1"))','chr(\\1)',' '), $body);
				$trans_tbl = get_html_translation_table (HTML_ENTITIES);
				$trans_tbl = array_flip ($trans_tbl);
				if(strtoupper($charset)==='UTF-8')
					$trans_tbl = array_map('utf8_encode',$trans_tbl);
				$body = strtr ($body, $trans_tbl);
				$body = trim(preg_replace(array("/<(br|\/?(p|tr))(?![a-z0-9])[^>]*?".">/i",'!^[ \t]+|[ \t]+$!m',"!(\r?\n){2,}!"),array("\n",'',"\n\n"),$body));
			}
		}
		if(in_array($ctype,array('plain','txt','text'))){ # in array used only for user facility
			$this->msg_plain = array();
			self::set_header($this->msg_plain,'Content-Type',"text/plain; charset=$charset;",FALSE,$charset);
			self::set_header($this->msg_plain,'Content-Type',"format=flowed",TRUE,$charset);
			if(self::$preferedEncoding==='quoted-printable'){
				self::set_header($this->msg_plain,'Content-Transfer-Encoding','quoted-printable',FALSE,$charset);
				$this->msg_plain['datas'] = $this->quoted_printable_encode($body);
			}else{
				self::set_header($this->msg_plain,'Content-Transfer-Encoding',self::$preferedEncoding,FALSE,$charset);
				$this->msg_plain['datas'] = $body;
			}
		}
	}
	/**
	* function by Allan Hansen contributed to HTML Mime Mail class from Richard Heyes <richard.heyes@heyes-computing.net>
	*
	*/
	function quoted_printable_encode($input , $line_max = 76){
		$lines  = preg_split("/(?:\r\n|\r|\n)/", $input);
		$eol    = "\n";
		$escape = '=';
		$output = '';
		$strlenFunc = 'strlen';
		if( defined('MB_OVERLOAD_STRING') ){
			$mbOverLoading = ini_get('mbstring.func_overload');
			if( ($mbOverLoading & MB_OVERLOAD_STRING) === MB_OVERLOAD_STRING){
				$strlenFunc = create_function('$str','return mb_strlen($str,"iso-8859-1");');
			}
		}

		while(list(, $line) = each($lines)){
			$linlen  = strlen($line);
			$newline = '';
			for($i = 0; $i < $linlen; $i++){
				$char = substr($line, $i, 1);
				$dec  = ord($char);
				if(($dec == 32) AND ($i == ($linlen - 1))){          // convert space at eol only
					$char = '=20';
				}elseif($dec == 9){
					;                                                  // Do nothing if a tab.
				}elseif(($dec == 61) OR ($dec < 32 ) OR ($dec > 126)){
					if($strlenFunc!=='strlen' && ($clen=$strlenFunc($char)) > 1){ #- special case when using mbstring.func_overload with utf8 encoding
						$_char = $char;
						$char = '';
						for($y=0;$y<$clen;$y++)
							$char .= $escape.strtoupper(sprintf('%02X', ord($_char{$y})));
					}else{
						$char = $escape.strtoupper(sprintf('%02X', $dec));
					}
				}
				if((strlen($newline) + strlen($char)) >= $line_max){ // CRLF is not counted
					$output  .= $newline.$escape.$eol;                 // soft line break; " =\r\n" is okay
					$newline  = '';
				}
				$newline .= $char;
			}                                                      // end of for
			$output .= $newline.$eol;
		}
		return $output;
	}

 /**
 * prepare the mail header before sending.
 * generally only used as an internal method
 * @param array $headers key/value pair of headers. If given then only thoose headers will be returned (used for parts headers)
 *                            if null then the mail headers will be returned
 */
	function make_header($headers=null){
		if(is_null($headers))
			$headers = &$this->headers;
		if( @is_array($this->parts['attachment']))
			$ctype = 'mixed';
		elseif(isset($this->msg_html) && isset($this->msg_plain))
			$ctype = 'alternative';
		elseif(isset($this->parts['inline']))
			$ctype = 'related';
		else
			$ctype = (isset($this->msg_html)?'html':'plain');

		# set the mime type
		self::set_header($headers,'MIME-Version','1.0',false);
		if( in_array($ctype,array('mixed','alternative','related')) ){
			self::set_header($headers,'Content-Type',"multipart/$ctype;",false);
			self::set_header($headers,'Content-Type',"boundary=$this->boundary",true);
		}else{
			self::set_header($headers,'Content-Type','text/'.$ctype.'; charset="'.self::$dfltHeaderCharset.'"',false);
			self::set_header($headers,'Content-Transfer-Encoding',self::$preferedEncoding,false);
			# self::set_header($headers,'Content-Transfer-Encoding','8bit',false);
		}
		if(! is_array($headers)) # no reason 4 this 2 happen or there's some really weird thing in this world
			return FALSE;

		return  implode("\n",$headers)."\n";
	}

	/**
	* send the mail accordingly to previously defined recipient(s) or/and the optionnaly given one
	* @param string $subject  optionnal subject
	* @param string $TO       optionnal email adress to send the mail to (must be passed if you haven't use easymail::to() before)
	* @param bool   $cleanTO  if set to true then empty the $_to array before adding new recipients.
	* @return bool
	*/
	function send($SUBJECT=null,$TO=null,$cleanTO=false){
		if($cleanTO)
			$this->_to = array();
		if(!is_null($TO))
			$this->to($TO);
		if(! isset($this->_to[0]))
			return FALSE; # we need one valid TO address at least
		$To = implode(",",$this->_to);
		# The subject part
		if(! is_null($SUBJECT) )
			$this->subject($SUBJECT);

		# then the message part
		$headers = $this->make_header();

		$_alternative   = ( (isset($this->msg_html) && isset($this->msg_plain) )? TRUE:FALSE );
		$is_multipart   = (@is_array($this->parts) || $_alternative )?TRUE:FALSE;
		$_inline_parts  = isset($this->parts['inline']);
		$msg = ($is_multipart?"This is a multi-part message in MIME format.\n\n":'');
		# first check for additional multipart message headers
		if( (!empty($this->parts['attachment'])) && ($_alternative || $_inline_parts)){ # we need some extra header to encapsulate the message
			$msg_type  = ($_alternative?'multipart/alternative':($_inline_parts?'multipart/related':'text'));
			$msg .= "--$this->boundary\nContent-Type: ";
			if($msg_type !== 'text'){
				$msg_bound = '--msg'.md5(uniqid(rand(),true));
				$msg .= "$msg_type;\n\tboundary=$msg_bound\n\n";
			}else{
				if( isset($this->msg_html) ){
					$msg.= $this->msg_html['content-type']."\nContent-Transfer-Encoding: ".$this->msg_html['content-transfer-encoding']."\n\n";
				}else{
					$msg.= $this->msg_plain['content-type']."\nContent-Transfer-Encoding: ".$this->msg_plain['content-transfer-encoding']."\n\n";
				}
			}
		}
		# set message part boundary
		$msg_bound = (isset($msg_bound)?$msg_bound:$this->boundary);

		# add the plain text msg
		if(isset($this->msg_plain)){
			$msg .= ($is_multipart?$this->get_part($this->msg_plain,$msg_bound):$this->msg_plain['datas']."\n");
		}

		# then the html part
		if( isset($this->msg_html)){
			if( $_alternative && isset($this->parts['inline'])){ # we need some extra headers for related inline parts
				$rel_bound = '--rel'.md5(uniqid(rand(),true));
				$msg .= "--$msg_bound\nContent-Type: multipart/related;\n\tboundary=$rel_bound\n\n";
			}
			$msg .= ($is_multipart?$this->get_part($this->msg_html,(isset($rel_bound)?$rel_bound:$msg_bound)):$this->msg_html['datas']."\n");
		}

		# then inline parts
		if(isset($this->parts['inline'])){
			foreach($this->parts['inline'] as $part)
				$msg .= $this->get_part($part,(isset($rel_bound)?$rel_bound:$msg_bound));
		}

		if(isset($rel_bound)) $msg .= "--$rel_bound--\n\n";
		if(isset($msg_bound) && $msg_bound != $this->boundary) $msg .= "--$msg_bound--\n\n";

		# then the attachment parts
		if(isset($this->parts['attachment'])){
			foreach($this->parts['attachment'] as $part)
				$msg .= $this->get_part($part,$this->boundary);
		}

		if($is_multipart)
			$msg .= "--$this->boundary--\n";

		return mail($To,$this->_subject,$msg,$headers);
	}

	/**
	* send a template email after a replacement inside the mail
	* @param string $to      a single mail adress
	* @param string $subject mail subject
	* @param string $doc     the path to the mail template or the template as a string itself
	* @param string $from    mail adress to use as sender adress
	* @param array  $datas   list of keys/values pair of replacement to make inside the template.
	* @param string $type    one of html|plain|both
	*/
	static public function mailTpl($to,$subject,$doc,$from,$datas,$type='html'){
		static $prepareKeys;
		if(! isset($prepareKeys))
			$prepareKeys = create_function('$e','return "---$e---";');

		//test du document
		if(strlen($doc) <255 && is_file($doc))
			$doc = file_get_contents($doc);

		$keys   = array_map($prepareKeys, array_keys($datas));
		$values = array_values($datas);
		$body   = str_replace($keys,$values,$doc);

		$easymail = new easymail();
		if(! $easymail->from($from) )
			return false;

		$easymail->body(
			$body,
			($type!=='plain'?'html':'plain'),
			($type==='both'?true:false),
			self::$dfltHeaderCharset
		);

		return $easymail->send($subject,$to);
	}

}
