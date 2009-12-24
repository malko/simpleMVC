<?php
/**
* extended simpleXMLElement
* @licence dual licence LGPL/MIT
* @author jonathan gotti <jgotti at jgotti dot net>
* @class verySimpleXMLElement
* @since 2009-12-15
*/
class verySimpleXMLElement extends SimpleXMLElement{
	/**
	* add a child to the current element and return it in the same way as original
	* simpleXMLElement but without the original value verification that sometimes is just a mess.
	* @param string $nodeName
	* @param string $value
	* @param string $nameSpace
	* @return verySimpleXMLElement
	*/
	public function addChild($nodeName,$value=null,$nameSpace=null){
		$node = parent::addChild($nodeName,null,$nameSpace);
		if( $value !== null ){
			#- $node->{0} = $value; <<-- this should work but doesn't on one production server of ours so replaced by next lines
			$nnode= dom_import_simplexml($node);
			$doc = $nnode->ownerDocument;
			$nnode->appendChild($doc->createTextNode($value));
		}
		return $node;
	}
	/**
	* same as addChild method but the value will be put inside a CData section.
	* @param string $nodeName
	* @param string $value
	* @param string $nameSpace
	* @return verySimpleXMLElement
	*/
	public function addCDataChild($nodeName,$value,$nameSpace=null){
		$node = $this->addChild($nodeName,null,$nameSpace);
		return $node->addCData($value);
	}
	/**
	* add a CData section to the current element and return this for method chaining
	* @param string $cdata
	* @return $this
	*/
	public function addCData($cdata){
		$node= dom_import_simplexml($this);
		$doc = $node->ownerDocument;
		$node->appendChild($doc->createCDATASection($cdata));
		return $this;
	}
	/**
	* exactly the same as simpleXMLElement::addAttribute()
	* but return this for method chaining
	* @param string $nodeName
	* @param string $value
	* @param string $nameSpace
	* @return $this
	*/
	public function addAttribute($string,$value,$nameSpace=null){
		parent::addAttribute($string,$value,$nameSpace);
		return $this;
	}
	/**
	* append a [very]SimpleXmlElement
	* @param SimpleXmlElement $child passed by reference
	*        (must be done if we want further modification to the $child element to be applyed to the document)
	* @return $this
	*/
	public function appendChild(SimpleXMLElement &$child){
		list($node,$_child) = self::getSameDocDomNodes($this,$child);
    $node->appendChild($_child);
		$child = simplexml_import_dom($_child);
		return $this;
	}
	/**
	* remove given [very]SimpleXmlElement from current element
	* @return $this
	*/
	public function removeChild(SimpleXMLElement $child){
		$node = dom_import_simplexml($this);
		$child = dom_import_simplexml($child);
		$node->removeChild($child);
		return $this;
	}
	/**
	* replace a child [very]SimpleXmlElement with another [very]SimpleXmlElement
	* @param SimpleXmlElement $newChild passed by reference
	*        (must be done if we want further modification to the newChild element to be applyed to the document)
	* @param SimpleXmlElement $oldChild
	* @return $this
	*/
	public function replaceChild(SimpleXmlElement &$newChild,SimpleXmlElement $oldChild){
		list($oldChild,$_newChild) = self::getSameDocDomNodes($oldChild,$newChild);
		$oldChild->parentNode->replaceChild($_newChild,$oldChild);
		$newChild= simplexml_import_dom($_newChild);
		return $this;
	}
	/**
	* remove a [very]SimpleXmlElement from it's parent
	* @return $this
	*/
	public function remove(){
		$node = dom_import_simplexml($this);
		$node->parentNode->removeChild($node);
		return $this;
	}
	/**
	* replace current element with another [very]SimpleXmlElement
	* @param SimpleXmlElement $replaceElmt passed by reference
	*        (must be done if we want further modification to the $replaceElmt element to be applyed to the document)
	* @return $this
	*/
	public function replace(SimpleXmlElement &$replaceElmt){
		list($node,$_replaceElmt) = self::getSameDocDomNodes($this,$replaceElmt);
		$node->parentNode->replaceChild($_replaceElmt,$node);
		$replaceElmt = simplexml_import_dom($_replaceElmt);
		return $this;
	}
	/**
	* static utility method to get two dom elements and ensure that the second is part of the same document than the first given.
	* @param SimpleXmlElement $node1
	* @param SimpleXmlElement $node2
	* @return array(DomElement,DomElement)
	*/
	static public function getSameDocDomNodes(SimpleXMLElement $node1,SimpleXMLElement $node2){
		$node1 = dom_import_simplexml($node1);
		$node2 = dom_import_simplexml($node2);
		if(! $node1->ownerDocument->isSameNode($node2->ownerDocument) )
			$node2 = $node1->ownerDocument->importNode($node2, true);
		return array($node1,$node2);
	}

}