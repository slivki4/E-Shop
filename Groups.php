<?php

class Groups {
	private static $cache = [];
	private static $current = null;
	public static $parents = [];	
	
	public static function getAll() {
		if (empty(self::$cache)) {
			$groups = YanakAPI::instance()->apiRequest('groups', 'GET');
			foreach($groups->items as $group) {
				self::$cache[$group->id] = $group;
			}
		}

		return self::$cache;
	}
	
	public static function get($groupID) {
		if (empty(self::$cache[$groupID])) 
			self::getAll();

		return self::$cache[$groupID];
	}	


	public static function getCurrent() {
		return self::$current;
	}

	public static function setCurrent($group){
		self::$current = $group;
	}
	
	public static function getChildren($groupId) {
		self::getAll();

		$children = [];
		foreach (self::$cache as $group) {
			if ($group->parentID === $groupId) {
				$children[] = $group->id;
				$children = array_merge($children, self::getChildren($group->id));
			}
		}

		return $children;
	}

	public static function getAncestors($groupId, $get_terms_as_ids) {
		self::getAll();
		$ancestors = [];

		while ($groupId > -1 && !empty(self::$cache[$groupId]) && self::$cache[$groupId]->parentID > -1) {
			$ancestors[] = $get_terms_as_ids ? self::$cache[$groupId]->id : Groups::generateWPTermFromYSGroupId($groupId);
			$groupId = self::$cache[$groupId]->parentID;
		}
		
		return $ancestors;

	}
	
	public static function getParents($group) {
		self::$parents[$group->id] = $group->name;
		foreach(self::$cache as $item){
			if($item->id === $group->parentID) {
			 return	self::getParents($item);
			}
		}
		self::$parents = array_reverse(self::$parents, true);
	}
	
	public static function getMultipleGroups($return_terms_as_ids = false, $parent_group_id, $include_ids){
		self::getAll();

		if ($parent_group_id === 0) {
			$parent_group_id = -1;
		}

		$terms = [];
		foreach (self::$cache as $group) {

			$term_id = YS_TERM_PREFIX + $group->id; //id-tata na grupite ot api-to shte gi puskame s YS_TERM_PREFIX + id, che da se razlichavat ot id-tata na wordpress terms

			if ($parent_group_id != '' && $parent_group_id !== $group->parentID && !in_array($term_id, $include_ids)) {
				continue;
			}

			if (count($include_ids) > 0 && !in_array($term_id, $include_ids)) {
				continue;
			}

			if ($return_terms_as_ids) {
				$terms[] = $term_id;
			} else {
				$term = self::generateWPTermFromYSGroupId($group->id);
				$terms[] = $term;
			}
		}
		
		return $terms;
	}

	// взима groupID и връща WP_Term
	public static function generateWPTermFromYSGroupId($groupId) {
		$term_id = YS_TERM_PREFIX + $groupId;

		$group = self::get($groupId);

		$term = WP_Term::get_instance(DUMMY_CATEGORY_ID);	// TODO: tova e hubavo da se cache-ira che se vika po 1000 puti na request
		$term->term_id = $term_id;
		$term->taxonomy = 'product_cat';
		$term->name = $group->name;
		$term->slug = $group->name.'-'.$group->id;
		$term->object_id = DUMMY_PRODUCT_ID;

		if ($group->parentID !== -1) {
			$term->parent = YS_TERM_PREFIX + $group->parentID;			
		}

		return $term;
	}
	

}
