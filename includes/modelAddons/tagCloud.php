<?php
/**
* modelAddon to easily create tagCloud models.
* add the common methods needed to manage tagClouds model.
* @package class-db
* @subpackage modelAddons
* @license http://opensource.org/licenses/lgpl-license.php GNU Lesser General Public License
* @author jonathan gotti <jgotti at jgotti dot org>
* @since 2008-05
*/
class tagCloudModelAddon extends modelAddon{
	static public $cloudParams = array(
		'minSize'  => '8',
		'maxSize'  => '26',
		'unitSize' => 'px',
		'separator'=>', ',
	);

	protected $taggableModels = array();

	public function __construct(abstractModel $modelInstance,$PK=null){
		parent::__construct($modelInstance,$PK);
		#- get taggableModels
		$hasMany = abstractModel::_getModelStaticProp($this->modelInstance,'hasMany');
		foreach($hasMany as $relName=>$relDef){
			if( empty($relDef['linkTable']) || empty($relDef['linkLocalField']) )
				continue;
			$this->taggableModels[] = $relName;
		}
	}
	/**
	* return a list of time a tag was used indexed by tagId
	* the list is ordered from most popular to less popular.
	* @param int    $limit            limit the results to the $limit most popular tags
	* @param bool   $returnCollection if true then only return the collection corresponding to each tags
	* @param string $taggable         name of taggable object we want tags popularity for. by default take the first taggable model found
	* @return array(tagId=>nbItems) or modelCollection depend on $returnCollection
	*/
	function getMostPopular($limit=50,$returnCollection=false,$taggable=null){
		#- get relDef for requested taggable
		if( is_null($taggable) ){
			if( empty($this->taggableModels) )
				return false;
			$taggable = reset($this->taggableModels);
		}

		$db      = abstractModel::_makeModelStaticCall($this->modelInstance,'_getDbAdapter');
		$relDef  = abstractModel::_getModelStaticProp($this->modelName,'hasMany');
		$relDef  = $relDef[$taggable];

		$res = $db->select_associative($relDef['linkTable'],"$relDef[linkLocalField] as t,count($relDef[linkForeignField]) as ct",'GROUP BY t ORDER BY ct DESC '.($limit?"LIMIT 0,$limit":''),'t','ct');
		if($res===false)
			$res = array();
		return $returnCollection?abstractModel::getMultipleModelInstances($this->modelName,array_keys($res)):$res;
	}

	/**
	* retourne un nuage de tag contenant $limit elements.
	* @param str  $tagStr     chaine de formatage passée à la fonction sprintf pour formater les retours.
	*                         les arguments passé à sprintf seront dans l'ordre:  tag->nom, tagSize, tag->tagId
	* @param int  $limit      nombre d'elements les plus populaires dans le nuages si limit est vide alors retourne le nuage entier
	* @param str  $sortType   dans quel ordre sont retourné les tags possibles sont:
	*                         - pop     -> du plus populaire au moins populaire (ordre par defaut)
	*                         - rpop    -> inverse de pop
	*                         - nat     -> ordre naturel sur le nom
	*                         - rnat    -> inverse de nat
	*                         - natcs   -> ordre naturel sensible a la casse sur le nom
	*                         - rnatcs  -> inverse de natcs
	*                         - shuffle -> mélangés
	* @param string $taggable name of taggable object we want tags popularity for. by default take the first taggable model found
	*/
	function getCloud($tagStr='<a href="?tag=%3$s" style="font-size:%2$s">%1$s</a>',$limit=50,$sortType='pop',$taggable=null){
		$tagsPopularity=self::getMostPopular($limit,false,$taggable);
		if( empty($tagsPopularity) )
			return '';
		#get max and min count for tags
		$counts = array_values($tagsPopularity);
		$maxCount = $counts[0];
		$minCount = end($counts);
		$delta = $maxCount - $minCount;
		if ($delta < 1)
    	$delta = 1;
    #- step of increment
    extract(self::$cloudParams);
    $sizeStep = ($maxSize - $minSize) / $delta;
    # get collection from array
    $tagsNames = abstractModel::getMultipleModelInstances($this->modelName,array_keys($tagsPopularity))->nom;
    $res = array();
    # on pretri la collection
    $reverseAtEnd = false;
    if( count($tagsNames) > 1){
			$sortType = strtolower($sortType);
			switch($sortType){
				case 'rpop':
					$reverseAtEnd=true;
					break;
				case 'rnat':
				case 'nat':
					natsort($tagsNames);
					$reverseAtEnd = $sortType[0]==='r'?true:false;
					break;
				case 'rnatcs':
				case 'natcs':
					natcasesort($tagsNames);
					$reverseAtEnd = $sortType[0]==='r'?true:false;
					break;
				case 'pop':
				default:
					foreach($tagsPopularity as $k=>$v)
						$tmpTagsNames[$k] = $tagsNames[$k];
					$tagsNames = $tmpTagsNames;
					// do nothing that's already ok
					break;
			}
		}
    foreach($tagsNames as $tagId=>$nom){
    	$tagSize = $minSize + (($tagsPopularity[$tagId] - $minCount) * $sizeStep);
    	$res[] = sprintf($tagStr,$nom,$tagSize.$unitSize,$tagId);
    }
    if( $reverseAtEnd)
    	$res = array_reverse($res);
    elseif('shuffle'===$sortType)
    	shuffle($res);
		return implode($separator,$res);
	}
}
