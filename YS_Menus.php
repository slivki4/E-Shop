<?php

class YS_Menus implements IYS_Hooks{
  
  private static $locations = null;
	public static function initHooks(){
		self::$locations = get_nav_menu_locations();
		add_filter('wp_get_nav_menu_items', array(__CLASS__, 'get_nav_menu_items'), 10, 3);
  }
  
  public static function get_nav_menu_items($items, $menu, $args ){
		if(self::$locations['main_menu'] && self::$locations['main_menu'] == $menu->term_id && $args['post_status'] === 'publish') {
			foreach($items as $key => $value) {
				if((int)$value->object_id === DUMMY_CATEGORY_ID) {
					$offset = $key;
					$groups = Groups::getAll();
					$count = count($groups);
				}

				if(isset($offset)) $value->menu_order = ++$count;	
			}

			

			if(isset($offset)) {
				global $wp_rewrite;
				$termlink = $wp_rewrite->get_extra_permastruct($items[$offset]->object);
				$ys_cats = [];
				$i = $offset;
				foreach($groups as $value) {
					$slug = ys_generate_slug($value->name, $value->id);
					$ys_cats[$i] = clone $items[$offset];
					$ys_cats[$i]->ID = YS_TERM_PREFIX + $value->id;
					$ys_cats[$i]->title = $value->name;
					$ys_cats[$i]->url = site_url().'/'.str_replace("%product_cat%", $slug, $termlink).'/';	
					$ys_cats[$i]->post_name = (string)$ys_cats[$i]->ID;
					$ys_cats[$i]->menu_order = $i+1;
					$ys_cats[$i]->db_id = $ys_cats[$i]->ID; 
					$ys_cats[$i]->object_id = (string)$value->id;
					if($value->parentID !== -1) $ys_cats[$i]->menu_item_parent = (string)YS_TERM_PREFIX + $value->parentID;
					if($value->parentID !== -1) $ys_cats[$i]->post_parent = $value->parentID;
					$i++;
				}	
				array_splice($items, $offset, 1, $ys_cats);
			}
		}

		else if(self::$locations['top_nav'] && self::$locations['top_nav'] == $menu->term_id && $args['post_status'] === 'publish') {
			if(!YS_User::isLoggedIn()) {
				$myaccountPageID = wc_get_page_id('myaccount');
				foreach($items as $key => $value) {
					if($myaccountPageID === (int)$value->object_id) {
						unset($items[$key]);
					}
				}
			} 
		}

		return $items;
	}

}


