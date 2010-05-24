<?php
/**
* Model Addon for preordered modified Tree traversal
* this modelAddon is good to use for small tree that are mostly used for menu
* or stuff like that. It's particularly optimized for fast select queries,
* but it's paricularly slow when modifying the tree as it require a lot of updates.
* @package class-db
* @subpackage modelAddons
* @license http://opensource.org/licenses/lgpl-license.php GNU Lesser General Public License
* @author jonathan gotti <jgotti at jgotti dot org>
* @since 2008-05
* @changelog 
*            - 2009-03-04 - change invalid variable name inside makeSibling method
*
to correctly handle this addon here's some static properties and methods that the given model must support

class sample_mpTreeTraversalNode implements mpTreeTraversalModelAddonInterface{
	static protected $treeFields = array(
		'right'=>'rightId','left'=>'leftId','id'=>'Id','level'=>'level',//'parent'=>'parentId'
	);
	static function getNew(){
		return self::newNode();
	}
	static function newNode(array $datas=null){
		return mpTreeTraversalModelAddon::newModelNode(self::$modelName,$datas);
	}
	static function getTreeCollection($startnode=FALSE,$depth=FALSE,$removeStartNode=false,$fromDB=false){
		return mpTreeTraversalModelAddon::getModelTreeCollection(self::$modelName,$startnode,$depth,$removeStartNode,$fromDB);
	}
	static function HtmlOptions($labelFld,$selected=null,$removed=null,$startnode=null,$depth=null){
		return mpTreeTraversalModelAddon::modelHtmlOptions(self::$modelName,$labelFld,$selected,$removed,$startnode,$depth);
	}
	function delete(){
		#- ~ $clone = clone $this;
		$this->_modelAddons['mpTreeTraversal']->removeNode();
		parent::delete();
	}
}
*/
/**
* interface the model must implement to be sure that you don't forget
* to correctly modify your model final class to work properly with this addon
*/
interface mpTreeTraversalModelAddonInterface{
	/*// property required for models that implements this interface:
	static protected $treeFields = array(
		'right'=>'rightId','left'=>'leftId','id'=>'Id','level'=>'level',//'parent'=>'parentId'
	);
	*/
	static function getNew();
	static function newNode(array $datas=null);
	static function getTreeCollection($startnode=FALSE,$depth=FALSE,$removeStartNode=false,$fromDB=false);
	static function HtmlOptions($labelFld,$selected=null,$removed=null,$startnode=null,$depth=null);
	public function delete();
}

class mpTreeTraversalModelAddon extends modelAddon{
	/**
	* keep trace of treesCollection for each models
	*/
	static protected $treesCollections = array();

	/** old some datas about loaded trees for easier acces */
	static private $internals = array();

	/** default mptt fields names */
	static private $dfltTreeFields = array(
		'right'  => 'right',
		'left'   => 'left',
		'level'  => 'level',
		'id'     => 'id',
		#- ~ 'parent' => 'parent'
	);

	function __construct(abstractModel $modelInstance,$instancePK=null){
		if(! $modelInstance instanceof mpTreeTraversalModelAddonInterface )
			throw new Exception("only models that implement mpTreeTraversalModelAddonInterface can use mpTreeTraversal ModelAddon");

		parent::__construct($modelInstance,$instancePK);
		self::modelInitTree($this->modelName);
		if(isset(self::$treesCollections[$this->modelName]->datas[$instancePK]) ){
			$this->modelInstance->_setDatas(self::$treesCollections[$this->modelName]->datas[$instancePK],true,$instancePK,true);
			$this->modelInstance->setModelDatasTypes();
		}
	}

	static private function modelInitTree($modelName,$onlyInternals=false){
		if(isset(self::$treesCollections[$modelName]) )
			return;
		#- check model support this addon
		$addons = abstractModel::_modelGetSupportedAddons($modelName);
		if(! in_array('mpTreeTraversal',$addons) )
			throw new Exception(__class__.'::'.__function__."() $modelName doesn't seems to support mpTreeTraversalModelAddon");
		$internals = array(
			'tableName'  => abstractModel::_getModelStaticProp($modelName,'tableName'),
			'treeFields' => array_merge(self::$dfltTreeFields, abstractModel::_getModelStaticProp($modelName,'treeFields')),
			'dbAdapter'  => abstractModel::getModelDbAdapter($modelName),
		);
		self::$internals[$modelName] = $internals;
		if( $onlyInternals )
			return false;
		self::$treesCollections[$modelName] = 1; #- avoid infinite loop
		self::modelReloadTree($modelName);
	}

	static public function modelReloadTree($modelName){
		self::$treesCollections[$modelName] = self::getModelTreeCollection($modelName,null,null,false,true);
	}

	/**
	* this will return a modelCollection that contain the whole tree structure
	* it can also return only part of the tree
	* @param str $modelName name of models we want to retreives tree for
	* @param int $startnode this will be an integer or false,
	*        if its an integer we will get all children of the given nodeId,
	*        if its false we will get the entire tree structure
	* @param int $depth: this will be an integer, or false
	*        represents the number of levels to dig for data.
	* @param bool $removeStartNode whether or not to include parent in result set (only used in combination with $startnode)
	* @param bool $fromDB force retrieving datas from db (used internally by reloadTree)
	* @return array
	*/
	static public function getModelTreeCollection($modelName,$startnode=null,$depth=null,$removeStartNode=false,$fromDB=false){
		self::modelInitTree($modelName,$fromDB);#- check we have datas loaded if we want to work from cache
		extract(self::$internals[$modelName]);
		if(! $fromDB ){
			$res = self::$treesCollections[$modelName];
			#- ~ show('GET_TREE DBG',self::$treesCollections);
			if( $startnode ){
				if(! $startnode instanceof $modelName)
					$startnode = $res->filterBy($treeFields['id'],$startnode,'==')->current();
				if( null===$startnode )
					return new modelCollection($modelName);
				$res = $startnode->getChilds();
				if( ! $removeStartNode ){
					$res[] = $startnode;
					$res->sort($treeFields['left'],'std');
				}
			}
			if( $depth > 0){
				$res = $res->filterBy($treeFields['level'],($startnode?$startnode->{$treeFields['level']}:0)+$depth,'<=');
			}
			return $res;
		}
		#- optimised single query when asking for the whole tree.
		if( null===$startnode && null===$depth && false===$removeStartNode)
			return abstractModel::getAllModelInstances($modelName,null," ORDER BY $treeFields[left] ASC");

		$filter = array(0=>'');
		if($startnode){
			$parent_ = $this->dbAdapter->select_single_to_array($tableName,$treeFields,array("WHERE $treeFields[id] =?",$startnode));
			$inc     = $removeStartNode?'':'=';
			$filter   = array(
				0 => "WHERE $treeFields[left] >$inc 1? AND $treeFields[right] <$inc 2? ",
				1 => $parent_[$treeFields['left']],
				2 => $parent_[$treeFields['right']],
			);
		}

		if($depth){
			$nest_limit = isset($parent_)?$parent_[$treeFields['level']]+$depth:$depth;
			$level_adjustment = $nest_limit - $depth; // we'll use this later
			$filter[0] .= (empty($filter[0])?'WHERE ':'AND ')."$treeFields[level] <= 3? ";
			$filter[3] = $nest_limit;
		}else{
			$level_adjustment = (isset($parent_)?$parent_[$treeFields['level']]-1:0);
		}

		$filter[0] .= " ORDER BY $treeFields[left] ASC";

		return abstractModel::getFilteredModelInstances($modelName,$filter)->loadDatas();
  }

	static function newModelNode($modelName,array $datas=null){
		self::modelInitTree($modelName);
		$node = abstractModel::getModelInstance($modelName);
		extract(self::$internals[$modelName]['treeFields'],EXTR_PREFIX_ALL,'fld');
		$collection = self::$treesCollections[$modelName];
		$lastRight= count($collection)?max($collection->{$fld_right}):0;
		if($datas)
			$node->_setDatas($datas);
		$node->{$fld_left} = $lastRight+1;
		$node->{$fld_right} = $lastRight+2;
		$node->{$fld_level} = 1;
		$node->save();
		$collection->append($node);
		return $node;
	}

	###--- METHODS FOR MODELS THAT EMBED THE ADDON ---###
	/**
	* return the number of childs for the current node
	*/
	public function getChildsCount(){
		$treeFields = self::$internals[$this->modelName]['treeFields'];
		return ( $this->modelInstance->{$treeFields['right']} - $this->modelInstance->{$treeFields['left']} - 1)/2;
	}
	/**
	* return relativeLevel from given node
	* @param mixed $fromNode abstractModel or its PK
	* @return int
	*/
	public function getRelativeLevel($fromNode){
		if(! $fromNode instanceof $this->modelName){
			$fromNode = abstractModel::getInstance($this->modelName,$fromNode);
			if( false===$fromNode )
				throw new Exception(__class__."::getRelativeLevel($fromNode) given fromNode is not a valid $this->modelName node");
		}
		$levelField = self::$internals[$this->modelName]['treeFields']['level'];
		return $this->{$levelField} - $fromNode->{$levelField};
	}

	/**
  * check if current node is a descendant of $parent
  * @param mixed $node abstractModel or its PK
	* @return bool
  */
  public function isChildOf($node){
		if(! $node instanceof $this->modelName){
			$node = abstractModel::getInstance($this->modelName,$node);
			if( false===$node )
				throw new Exception(__class__."::isChildOf($node) givent node is not a valid $this->modelName node");
		}
		$treeFields = self::$internals[$this->modelName]['treeFields'];
		$ret = (bool)(($node->{$treeFields['left']} < $this->modelInstance->{$treeFields['left']}) &&
    ($node->{$treeFields['right']} > $this->modelInstance->{$treeFields['right']}));
		return $ret;
	}

	/**
	* return modelCollection of abstractModel
	* if node has no parent will return an empty collection.
	* @param bool $includeNode if true then include current node in the returned collection
	* @return modelCollection
	*/
	public function getPath($includeNode=false){
		extract(self::$internals[$this->modelName]['treeFields'],EXTR_PREFIX_ALL,'fld');
		$collection = self::$treesCollections[$this->modelName];
		$path = $collection->filterBy($fld_left,$this->modelInstance->{$fld_left},'<');
		$path = $path->filterBy($fld_right,$this->modelInstance->{$fld_right},'>');
		if( $includeNode )
			$path[] = $this->modelInstance;
		return $path->sort($fld_left,'std');
	}

	/**
	* return the parent of the current node
	* @param bool $returnPK if true then return the primaryKey instead of the node instance
	* @return mpTreeTraversalModelAddonInterface or int primary key depend on $returnPK parameter
	*         null if no parent were found (typically this is a root node)
	*/
	public function getParent($returnPK=false){
		$path = $this->getPath();
		if(! count($path))
			return null;
		$parent = $path->last();
		return $returnPK?$parent->PK:$parent;
		#- ~ return abstractModel::getModelInstance($this->modelName,$this->modelInstance->{self::$internals[$this->modelName]['treeFields']['parent']});
	}

	/**
	* retun modelCollection of abstractModel that are childs of the current node
	* @param int $depth by default return all childs but you can limit the result to a given depth.
	* @return modelCollection
	*/
	public function getChilds($depth=null){
		$i = $this->modelInstance;
		if($i->deleted)
			throw new Exception(__class__.'::getChilds() Trying to get childs on a deleted node('.$i->PK.')');
		extract(self::$internals[$this->modelName]['treeFields'],EXTR_PREFIX_ALL,'fld');
		$collection = self::$treesCollections[$this->modelName];
		$childs = $collection->filterBy($fld_left,$i->{$fld_left},'>')
							->filterBy($fld_right,$i->{$fld_right},'<');
		if( $depth > 0)
			$childs = $childs->filterBy($fld_level,$i->{$fld_level}+$depth,'<=');

		return $childs;
		#- ~ $maxDepth = ( null !== $depth )? $i->{$fld_level}+$depth : false;
		#- ~ $tmpCollection = new modelCollection($this->modelName);
		#- ~ foreach($collection as $item){
			#- ~ if(! $item->isChildOf($i) ) continue; # check is child
			#- ~ if( $maxDepth && $item->{$fld_level} > $maxDept ) continue; # check depth
			#- ~ $tmpCollection[] = $item->PK;
		#- ~ }
		#- ~ return $tmpCollection;
	}


	public function newChild(array $datas=null){
		if( null===$datas)
			$datas = array();
		extract(self::$internals[$this->modelName]);
		extract($treeFields,EXTR_PREFIX_ALL,'fld');
		$collection = self::$treesCollections[$this->modelName];
		$i = $this->modelInstance;
		$lid = $i->{$fld_left};
		$rid = $i->{$fld_right};
		$lvl = $i->{$fld_level};

		#- setting new nodes left/right and level datas
		$datas[$fld_left] = $rid;
		$datas[$fld_right]= $rid+1;
		$datas[$fld_level]= $lvl+1;

  	# make some space in database and collection
  	$this->dbAdapter->update($tableName,"$fld_left=$fld_left+2",array("WHERE $fld_left > ?",$rid));
  	foreach($collection->filterBy($fld_left,$rid,'>') as $n)
			$n->_setData($fld_left,$n->{$fld_left}+2,true,true);
  	$this->dbAdapter->update($tableName,"$fld_right=$fld_right+2",array("WHERE $fld_right >= ?",$rid));
  	foreach($collection->filterBy($fld_right,$rid,'>=') as $n)
			$n->_setData($fld_right,$n->{$fld_right}+2,true,true);

  	#- ~ $i->_setDatas(array("$fld_right"=>$i->{$fld_right}+2),true,null,true);

		#- insertNode in the collection and database
		$child = abstractModel::getModelInstanceFromDatas($this->modelName,$datas)->save();;
		$collection->append($child);
		$collection->sort($fld_left,'std');
		return $child;
	}

	/**
	* remove an item from the database and re-index left and right ids.
	* @param bool $returnremovedNodes
	*/
	function removeNode($returnremovedNodes=false){
  	$i = $this->modelInstance;
		extract(self::$internals[$this->modelName]);
		extract($treeFields,EXTR_PREFIX_ALL,'fld');
		$collection = self::$treesCollections[$this->modelName];

		#- remove nodes from database
		$filter = array(
			"WHERE $fld_right <= ? AND $fld_left >= ?",
			$i->{$fld_right},
			$i->{$fld_left},
		);
		if( false === $this->dbAdapter->delete($tableName,$filter))
    	throw new Exception(__class__.'::removeNode() Error while deleting nodes');

		#- remove nodes from collection
		$removedNodes = $i->getChilds();
		#- append current node to removedNode Collection
		$removedNodes->append($i);
		#- remove them from in memory collection
		foreach($removedNodes as $k=>$c)
			unset($collection[$k]);

		/* lets fix some right ids. this sets all right ids to the number of children * 2 (recognize the formula?)
		We use 2 because each node is worth 2 in our table, the 1 right id and 1 left id */
		$delta = $i->{$fld_right} - $i->{$fld_left} + 1;

		#- update right/left in database and memory
		$this->dbAdapter->update($tableName,"$fld_left=$fld_left-$delta",array("WHERE $fld_left > ?",$i->{$fld_left}));
		foreach($collection->filterBy($fld_left,$i->{$fld_left},'>') as $k=>$n)
			$n->_setData($fld_left,$n->{$fld_left}-$delta,true,true);
		$this->dbAdapter->update($tableName,"$fld_right=$fld_right-$delta",array("WHERE $fld_right > ?",$i->{$fld_right}));
		foreach($collection->filterBy($fld_right,$i->{$fld_right},'>') as $k=>$n)
			$n->_setData($fld_right,$n->{$fld_right}-$delta,true,true);

		// optimize the table to remove overhead
		$this->dbAdapter->optimize($tableName);

		if($returnremovedNodes){
			# reset level and ids to correct values as a separated tree
			$idDelta = $i->{$fld_left}-1;
			$lvlDelta= $i->{$fld_level} -1;
			foreach($removedNodes as $k=>$c){
				$datas = array(
					"$fld_left"=>$c->{$fld_left}-$idDelta,
					"$fld_right"=>$c->{$fld_right}-$idDelta,
					"$fld_level"=>$c->{$fld_level}-$lvlDelta,
				);
				$c->_setDatas($datas,true,null,true);
			}
			return $removedNodes->sort($fld_left,'std');
		}
		return TRUE;
  }

	/**
	* set parent to $newparent node
  * @param mixed $parent abstractModel or its PK
	* @return $this->modelInstance
	*/
	function reparent($parent=null){
		$i = $this->modelInstance;
		#- ~ $this->dbAdapter->beverbose=3;
		if( null!==$parent ){
			if(! $parent instanceof $this->modelName)
				$parent = abstractModel::getModelInstance($this->modelName,$parent);
			if( $parent->isChildOf($i) ) # daddy couldn't be a child
				throw new Exception(__class__."::reparent() can't reparent node($i->PK) to one of it's child");
		}
		extract(self::$internals[$this->modelName]);
		extract($treeFields,EXTR_PREFIX_ALL,'fld');

		#- remove nodes from their current emplacement
		$removedCollection = $this->removeNode(true);
		$collection = self::$treesCollections[$this->modelName];

		#- reset node datas
		if( null === $parent ){
			$idRmDelta  = count($collection)*2;
			$lvlRmDelta = 0;
		}else{
			#- datas to update inside memory
			$idRmDelta  = $parent->{$fld_right}-1;
			$lvlRmDelta = $parent->{$fld_level};
			#- prepare some space in tree (database / in memory)
			$idUpDelta = $i->{$fld_right}-$i->{$fld_left}+1;
			#- ~ $this->dbAdapter->update($tableName,"$fld_left=$fld_left+$idUpDelta",array("WHERE $fld_left > ?",$parent->{$fld_left}));
			#- ~ foreach($collection->filterBy($fld_left,$parent->{$fld_left},'>') as $node)
				#- ~ $node->_setData($fld_left,$node->{$fld_left}+$idUpDelta,true,true);
			$this->dbAdapter->update($tableName,"$fld_left=$fld_left+$idUpDelta",array("WHERE $fld_left > ?",$parent->{$fld_right}));
			foreach($collection->filterBy($fld_left,$parent->{$fld_right},'>') as $node)
				$node->_setData($fld_left,$node->{$fld_left}+$idUpDelta,true,true);
			$this->dbAdapter->update($tableName,"$fld_right=$fld_right+$idUpDelta",array("WHERE $fld_right >= ?",$parent->{$fld_right}));
			foreach($collection->filterBy($fld_right,$parent->{$fld_right},'>=') as $node)
				$node->_setData($fld_right,$node->{$fld_right}+$idUpDelta,true,true);
		}
		foreach($removedCollection as $k=>$node){
			$datas = array(
				"$fld_left"  => $node->{$fld_left}+$idRmDelta,
				"$fld_right" => $node->{$fld_right}+$idRmDelta,
				"$fld_level" => $node->{$fld_level}+$lvlRmDelta
			);
			$node->_setDatas($datas,true,null,true);
		}

		#- reinsert nodes in database
		foreach($removedCollection as $k=>$node){
			$collection[] = $node;
			$this->dbAdapter->insert($tableName,$node->datas);
		}

		#- reorder collection
		$collection->sort($fld_left,'std');
		return $this->modelInstance;
	}

	/**
	* return collection of current's brothers node (same parent same level)
	* @param bool $includeNode whether current node will be part of the result or not
	* @return modelCollection
	*/
	function getBrothers($includeNode=false){
		$parent = $this->getParent();
		if( null !== $parent)  #- get all brothers from parent
			$brothers = $parent->getChilds(1);
		else #- if no parent all node with level 1 are our brothers
			$brothers = self::$treesCollections[$this->modelName]->filterBy(self::$internals[$this->modelName]['treeFields']['level'],1);
		if( (! $includeNode ) && isset($brothers[$this->modelInstance->PK]) )
			unset($brothers[$this->modelInstance->PK]);
		return $brothers;
	}
	/**
	* return the next node of the same level or null if current node is last child
	* @return abstractModel or null
	*/
	function nextBrother(){
		$fld_left = self::$internals[$this->modelName]['treeFields']['left'];
		return $this->getBrothers()->filterBy($fld_left,$this->modelInstance->{$fld_left},'>')->first();
	}

	/**
	* return the next node of the same level or null if current node is first child
	* @return abstractModel or null
	*/
	function prevBrother(){
		$fld_left = self::$internals[$this->modelName]['treeFields']['left'];
		return $this->getBrothers()->filterBy($fld_left,$this->modelInstance->{$fld_left},'<')->last();
	}

	/**
	* move the given node to be the direct brother of the $sibling node
  * @param mixed $sibling abstractModel or its PK
	* @param bool  $younger if set to true then will be move before the sibling instead of next
	*/
	function makeSibling($sibling,$younger=FALSE){
		$i = $this->modelInstance;
		if( null!==$sibling ){
			if(! $sibling instanceof $this->modelName)
				$sibling = abstractModel::getModelInstance($this->modelName,$sibling);
			if( $sibling->isChildOf($i) ) # daddy couldn't become a brother
				throw new Exception(__class__."::makeSibling() can't make node($i->PK) a child of himself");
		}
		extract(self::$internals[$this->modelName]);
		extract($treeFields,EXTR_PREFIX_ALL,'fld');

		#- first remove node from its actual position
		$removedCollection = $this->removeNode(true);
		$collection = self::$treesCollections[$this->modelName];

		#- reset nodes datas
		if( null === $sibling ){
			$idDelta  = count($collection)*2;
			$lvlDelta = 0;
		}else{
			$idDelta  = $younger?$sibling->{$fld_left}-1:$sibling->{$fld_right};
			$lvlDelta = $sibling->{$fld_level}-1;
		}
		foreach($removedCollection as $k=>$node){
			$datas = array(
				"$fld_left"  => $node->{$fld_left}+$idDelta,
				"$fld_right" => $node->{$fld_right}+$idDelta,
				"$fld_level" => $node->{$fld_level}+$lvlDelta
			);
			$node->_setDatas($datas,true,null,true);
		}
		#- ~ $this->dbAdapter->beverbose=3;
		#- prepare some space in the tree
		$idUpDelta = $i->{$fld_right}-$i->{$fld_left}+1;
		$this->dbAdapter->update($tableName,"$fld_left=$fld_left+$idUpDelta",array("WHERE $fld_left >= ?",$i->{$fld_left}));
		foreach($collection->filterBy($fld_left,$i->{$fld_left},'>=') as $n)
			$n->_setData($fld_left,$n->{$fld_left}+$idUpDelta,true,true);
		$this->dbAdapter->update($tableName,"$fld_right=$fld_right+$idUpDelta",array("WHERE $fld_right >= ?",$i->{$fld_left}));
		foreach($collection->filterBy($fld_right,$i->{$fld_left},'>=') as $n)
			$n->_setData($fld_right,$n->{$fld_right}+$idUpDelta,true,true);

		#- reinsert nodes in database
		foreach($removedCollection as $k=>$node){
			$collection[] = $node;
			$this->dbAdapter->insert($tableName,$node->datas);
		}

		#- reorder collection
		$collection->sort($fld_left,'std');
		return $this->modelInstance;
	}

	/**
	* move up a node (keep the same parent).
	* if the node have any higher/older brother then it will make it higher in the hierarchie,
	* return $this->modelInstance
	*/
	function moveup(){
		$prevBrother = $this->prevBrother();
		if( null === $prevBrother)
			return $this->modelInstance;
		return $this->makeSibling($prevBrother,true);
	}
	/**
	* move down a node (keep the same parent).
	* if the node have any lower/younger brother then it will make it lower in the hierarchie,
	* return $this->modelInstance
	*/
	function movedown(){
		$nextbrother = $this->nextBrother();
		if( null === $nextbrother)
			return $this->modelInstance;
		return $this->makeSibling($nextbrother);
	}

	static function modelHtmlOptions($modelName,$labelFld,$selected=null,$removed=null,$startnode=null,$depth=null){
		self::modelInitTree($modelName);
		#- take a clonedCollection to permit datas transformation without impacting real instances
		$tree = self::getModelTreeCollection($modelName,$startnode,$depth)->clonedCollection();
		$fld_level = self::$internals[$modelName]['treeFields']['level'];
		if( null===$startnode ){
			$levelDelta = 1;
		}else{
			if(! $startnode instanceof $modelName ){
				$startnode = abstractModel::getModelInstance($modelName,$startnode);
				if( ! $startnode instanceof $modelName )
					throw new Exception("mpTreeTraversalModelAddon::modelHtmlOptions() request with an invalid startnode parameter ($startnode)");
			}
			$levelDelta = $startnode->{$fld_level};
		}
		$tree->map(create_function('$i','$i->'.$labelFld.' = str_repeat("&nbsp;",2*($i->'.$fld_level.'-'.$levelDelta.')).$i->'.$labelFld.';'));

		return $tree->htmlOptions("%$labelFld",$selected,$removed);
	}
  /**
  * get html option for select form input in a really simple way
  * @param string $show_fld the column name used as option's labels
  * @param string $val_fld the column name used as option's values
  * @param int $selected_value the optionnal selected option's value
  * @param int | array $removednode the nodes ids to remove
  * @uses HtmlDbTree::get_tree
  * @return string or FALSE
  */
  function html_get_options($show_fld,$val_fld='id',$selected_value=null,$removednode=null,$startnode=FALSE,$depth=FALSE,$inc_startnode=TRUE){
    if(!$items = $this->get_tree($startnode,$depth,$inc_startnode))
      return FALSE;
    $str = '';
    foreach($items as $item){
      if($removednode){
        if(is_array($removednode)){
          if( in_array($item[$val_fld],$removednode))
            continue;
          foreach($removednode as $node){
            if($this->is_child($item['id'],$node))
              continue 2;
          }
        }elseif(($removednode==$item[$val_fld]) || $this->is_child($item['id'], $removednode)){
          continue;
        }
      }
      $str .= '    <option value="'.$item[$val_fld].'"'
              .($item[$val_fld]==$selected_value?' selected="selected"':'').'>'
              .str_repeat('&nbsp;',max(0,2*($item['relative_level']-1)) ).$item[$show_fld]."</option>\n";
    }
    return $str;
  }

  /**
  * return a subset of links to get the path of previous node
  * @param str  $linkexp    is an expression with fields replacement
  *                         ie: "index.php?cat=%=id=%" where %=id=% will be replace by the value in the id field
  *                         so this could be id, left_id,right_id, level, and so on
  * @param str  $labelfield is the db field name to use as link label
  * @param int  $item
  * @param bool $keep_item  set it to false if you don't want to exclude the current item of the returned path
  * @param str  $stringglue string use as link separator
  * @return string or FALSE
  */
  function html_get_path($linkexp,$labelfield,$item,$keep_item=TRUE,$stringglue=' / '){
    if(! $paths = $this->get_path($item,$keep_item))
      return FALSE;
    preg_match_all('!%=([^\s=]+)=%!',$linkexp,$m);
    foreach($paths as $path){
      $url = $linkexp;
      if(count($m[1])){
        foreach($m[1] as $tag)
          $url =str_replace("%=$tag=%",$path[$tag],$url);
      }
      $str[] ='<a href="'.$url.'">'.$path[$labelfield].'</a>';
    }
    return implode($stringglue,$str);
  }

/*
don't seems to be needed now
	public function saveNode(){
		return;
		$i = $this->modelInstance;
		extract(self::$internals[$this->modelName]);
		extract($treeFields,EXTR_PREFIX_ALL,'fld');
		$collection = self::$treesCollections[$this->modelName];
  	#- if not temp instance only infos need update so nothing partical to do, let normal save to do the job.
		if(! $i->isTemporary() )
			return ;
		#- this is a new node so we must do some particular stuff before saving
		$parentNode = $i->getParent();
		if( null === $parentNode ){ #- this is a root node
  		$lastNode = end(self::$treesCollections[$this->modelName]);
  		$i->{$fld_left} = (false===$lastNode? 1 : $lastNode->{$fld_left}+1);
  		$i->{$fld_right}= $i->{$fld_left}+1;
  		$i->{$fld_level}= 1;
		}else{ #- we have a parent
			$i->{$fld_level} = $parentNode->{$fld_level}+1;
			$brothers = $this->getBrothers();
			if( count($brothers) > 0){ #- we have brothers
				$lastBroInfos = $brothers->last()->datas;
				$updateFrom =$lastBroInfos[$fld_right]+1;
				$iLeftId = $updateFrom+1;
			}else{ #- we are first child
				$iLeftId = $parentNode->{$fld_right};
				$updateFrom = $parentNode->{$fld_left};
			}
			#- update database
			$this->dbAdapter->update($tableName,"$fld_left = $fld_left +2","WHERE $fld_left > $updateFrom");
			$this->dbAdapter->update($tableName,"$fld_right = $fld_right +2","WHERE $fld_right > $updateFrom");

			#- update living instances
			foreach($collection->filterBy($fld_left,$updateFrom,'>') as $item)
				$item->_setDatas(array("$fld_left"=>$iLeftId+2),true,null,true);
			foreach($collection->filterBy($fld_right,$updateFrom,'>') as $item)
				$item->_setDatas(array("$fld_right"=>$iLeftId+3),true,null,true);

			#- then update current node ids
			$i->{$fld_left} = $iLeftId+1;
			$i->{$fld_right}= $iLeftId+2;
		}
		#- ensure node is part of the collection
		$collection[] = $i;
		$collection->sort($fld_left,'std'); # keep collection sorted
		# then leave normal save do the rest of the job
	}
*/
}

class HtmlDbTree_______{

  /**
  * add - adds an element from the SQL tree You must call load_tree after this one
  * @Public
  * @param  array   $info Array   Other coloumns
  * @param  Integer/false  $parent  Parent ID (false = root)
  * @return  true or the node id if autoincrement on id field is enabled
  **/
  function add($info,$parent=0){
    // does the object have a daddy?
    if(! empty($parent)){
      # WE HAVE A PARENT SO:
      # level = daddy level + 1
      $info['level'] = $this->datas[$parent]['level']+1;
      # check for brothers
      if($this->datas[$parent]['number_of_children']){ # WE HAVE BROTHERS
        # get the last daddy's child (the one with the highest left_id)
        $brother_info = $this->db->select_single_to_array($this->tablename,
                  $this->fields['right_id'].', '.$this->fields['left_id'].', '.$this->fields['id'],
                  'WHERE '.$this->fields['level'].' = '.($this->datas[$parent]['level']+1)
                          .' AND '.$this->fields['left_id'].' >= '.$this->datas[$parent]['left_id']
                          .' AND '.$this->fields['right_id'].' <= '.$this->datas[$parent]['right_id']
                          .' ORDER BY '.$this->fields['left_id'].' DESC');
        # left_id = youngest brother right_id + 1
        # right_id = left_id + 1
        $info['left_id']  = $brother_info[$this->fields['right_id']] + 1;
        $info['right_id'] = $info['left_id'] + 1;
        # all above will be update
        $update_past = $brother_info[$this->fields['right_id']];
      }else{ # WE ARE UNIQUE CHILD
        #update all greater than daddy's left ID
        $update_past =  $this->datas[$parent]['left_id'];
        $info['left_id'] =  $this->datas[$parent]['left_id'] + 1; # no brother so left_id = daddy's left_id +1
        $info['right_id'] = $info['left_id'] + 1 ;# right_id = left_id +1
      }
      # UPDATE ALL NODES IDS +2 ABOVE THE NEW ONE
      $this->db->update($this->tablename,$this->fields['left_id'].' = '.$this->fields['left_id'].' + 2',
                                          'WHERE '.$this->fields['left_id'].' > ' . $update_past);
      $this->db->update($this->tablename,$this->fields['right_id'].' = '.$this->fields['right_id'].' + 2',
                                          'WHERE '.$this->fields['right_id'].' > ' . $update_past);
    }else{
      # WE ARE ROOT NODE
      if(is_array($this->datas)){
        $rarray = array_reverse($this->datas);
        // his left id is one past the highest right id, which we can get with this formula
        $info['left_id'] = $rarray[0]['right_id'] + $rarray[0]['level'];
      }else{ # first node of the tree
        $info['left_id'] = 1;
      }
      $info['right_id'] = $info['left_id'] + 1;
      $info['level'] = 1;
    }
    # remap infarray
    $info['parent'] = $parent;
    foreach($this->default_fields as $fld){
      if($this->fields[$fld]!=$fld){
        $info[$this->fields[$fld]]=@$info[$fld];
        unset($info[$fld]);
      }
    }
    #INSERT NEW NODE
    return $this->db->insert($this->tablename,$info);
  }
  /**
  * add a new node as a younger or older  brother of the $brother node
  * @param array $info
  * @param  array   $info Array   Other columns
  * @param  Integer/false  $brother brother ID (false = root)
  * @return bool
  */
  function add_as_brother($info,$brother=0,$younger=TRUE){
    $info['level'] = $this->datas[$brother]['level'];
    # left_id = brother right_id + 1
    # right_id = left_id + 1
    if($younger)
      $info['left_id']  = ($update_past = $this->datas[$brother][$this->fields['right_id']])+ 1;
    else
      $info['left_id']  = ($update_past = $this->datas[$brother][$this->fields['left_id']]);
    $info['right_id'] = $info['left_id'] + 1;

    # UPDATE ALL NODES IDS +2 ABOVE THE NEW ONE
    $this->db->update($this->tablename,$this->fields['left_id'].' = '.$this->fields['left_id'].' + 2',
                                      'WHERE left_id >'.($younger==FALSE?'=':'').' ' . $update_past);
    $this->db->update($this->tablename,$this->fields['right_id'].' = '.$this->fields['right_id'].' + 2',
                                      'WHERE right_id > ' . $update_past);
    # remap infarray
    $info['parent'] = $this->datas[$brother]['parent'];
    foreach($this->default_fields as $fld){
      if($this->fields[$fld]!=$fld){
        $info[$this->fields[$fld]]=$info[$fld];
        unset($info[$fld]);
      }
    }
    # INSERT NEW NODE
    return $this->db->insert($this->tablename,$info);
  }

	/**
  * rebuild the tree (right_id,left_id,and level) from the parent column
  */
  # function rebuild_tree($parent_col,$parent_id=0,$left=1,$level=0){
  function rebuild_tree($parent_id=0,$left=1,$level=-1){
    // the right value of this node is the left value + 1
    settype($parent_id,'int');
    $right = $left+1;
    $level += 1;
    $items = $this->db->select_to_array( $this->tablename,$this->fields['id'],
                                "WHERE ".$this->fields['parent']." = '$parent_id' ORDER by ".$this->fields['left_id'].' ASC');
    if($items)
      foreach($items as $item)
        $right = $this->rebuild_tree($item[$this->fields['id']],$right,$level);

    $this->db->update($this->tablename,array($this->fields['left_id']=>$left,
                                             $this->fields['right_id']=>$right,
                                             $this->fields['level']=>$level,)
                      ,"WHERE ".$this->fields['id'] ." = '$parent_id'");
    return $right+1;
    $this->load_tree();
  }

  /**
  * return parents node of this item
  * @param int $item the item to retrieve path to
  * @param bool $keep_item include item in path or not
  * @param bool $dont_use_parent the default way this method work is to use the parent propertie of a node
  *                              (thx Lasse Lofquist <lass_phpclass at tvartom.com>) if set to true then it
  *                             will use the is_child method instead (use it when there's no parent id or when you don't trust them)
  *                             and so will only trust the right and left ids of nodes
  * @return array indexed by level
  */
  function get_path($item,$keep_item=TRUE,$onlyids=FALSE,$dont_use_parent=FALSE){
    if(! isset($this->datas[$item]) ) return FALSE;
    if(! $dont_use_parent){ # really better way to do the same using parent ids by Lasse Lofquist
      if($keep_item)
        $path_array[$this->datas[$item]['level']] = ($onlyids ? $item : $this->datas[$item]);
      while ($item = $this->datas[$item]['parent'])
        $path_array[$this->datas[$item]['level']] = ($onlyids ? $item : $this->datas[$item]);
      return(isset($path_array)?array_reverse($path_array, true):null);
    }else{ # old fashion way that don't use the parent id but slower
      foreach($this->datas as $item_id => $item_data){
        if($this->is_child($item, $item_id)){
          $path_array[$item_data['level']] = ($onlyids?$item_id:$item_data);
          # we have all the data we need for path
          if($item_data['relative_level'] == ($this->datas[$item]['relative_level']-1))
            break;
        }
      }
      if( $keep_item )
        $path_array[$this->datas[$item]['level']] = ($onlyids?$item:$this->datas[$item]);
      return isset($path_array)?$path_array:null;
    }
  }
  /**
  * return the extended path to the node with all ancestors expanded
  * the principle is pretty simple we take all nodes and remove nodes that aren't direct child of ancestors.
  * the first level nodes will always be displayed
  * @param int $item
  */
  function get_extended_path($item=0,$withchild=TRUE,$onlyids=FALSE){
    if(! $this->datas) return FALSE;
    if( $item==0 || ($this->datas[$item]['level'] < 1) || (! is_array($ancestors = $this->get_path($item))) ) # first level node so return only the first level nodes
      return $this->get_tree(null,1);
    $items = $this->datas;
    $curlevel = $this->datas[$item]['level'];
    # removed unnecessary nodes
    foreach($items as $id=>$item){
      $ids[$id] = $id;
      if($item['level']==1)continue; # first level nodes have to be keeped
      # remove child with too high level
      if($item['level']>$curlevel && !$withchild){
        unset($items[$id],$ids[$id]);continue;
      }elseif($item['level']>$curlevel+1){
        unset($items[$id],$ids[$id]);continue;
      }
      # keep brothers of ancestors nodes
      if($this->is_child($id,$ancestors[$item['level']-1]['id']) ) continue;
      # all other childs have to be removed
      unset($items[$id],$ids[$id]);
    }
    return ($onlyids?$ids:$items);
  }


}
