<?php
/**
* @require verySimpleXMLElement
*
#- http://www.rssboard.org/rss-specification
*/
class rssItemModelAddon extends  modelAddon{
	private static $_modelItemFieldsMapping = array(
		'title'       => 'title',
		'link'        => 'link',
		'description' => 'description',
		'author'      => null, //Email address of the author of the item.
		'category'    => null, //Includes the item in one or more categories.
		'pubDate'     => 'auto', // Indicates when the item was published. (auto will default to now)
		'comments'    => null, // URL of a page for comments relating to the item.
		#- 'enclosure'   => null, //	Describes a media object that is attached to the item.
		'guid'        => 'PK', //A string that uniquely identifies the item.
		'source'      => null, //The RSS channel that the item came from.


	);
	private static $_internal = array();
	public $modelItemFieldsMapping = array();

	public function __construct(abstractModel $modelInstance,$PK=null){
		parent::__construct($modelInstance,$PK);
		$this->modelItemFieldsMapping = self::$_internal[$this->modelName];
	}
	protected function _initModelType(){
		if( property_exists($this->modelName,'_modelItemFieldsMapping') ){
			$mapping = array_merge(self::$_modelItemFieldsMapping,abstractModel::_getModelStaticProp($this->modelName,'_modelItemFieldsMapping'));
		}else{
			$mapping = self::$_modelItemFieldsMapping;
		}
		self::$_internal[$this->modelName] = $mapping;
	}
	function getRssItem(){
		$mapping = array_filter($this->modelItemFieldsMapping,'is_scalar');
		$item = new verySimpleXMLElement('<item />');
		foreach( $mapping as $k=>$v){
			switch($k){
				case 'pubDate':
					if( $v==='auto'){
						$item->addChild($k,date('r'));
						break;
					}elseif(preg_match('!^\s*\d\d\d\d\D\d\d\D\d\d!',$this->modelInstance->{$v})){
						$item->addChild($k,date('r',strtotime($this->modelInstance->{$v})));
						break;
					}
				default:
					$item->addChild($k,(string)$this->modelInstance->{$v});
			}
		}
		return $item;
	}
	function appendRssItemTo(verySimpleXMLElement $channel){
		$item = $this->getRssItem();
		$channel->appendChild($item);
	}
}