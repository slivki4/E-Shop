<?php 

add_action( 'wpmu_new_blog', '_wpmu_new_blog', 10, 6 );
/**
 * Като се създава нов сайт, това ще копира таблиците от демо сайта в новия, така че всичко да му работи
 * 
 * @param int    $blog_id Blog ID.
 * @param int    $user_id User ID.
 * @param string $domain  Site domain.
 * @param string $path    Site path.
 * @param int    $site_id Site ID. Only relevant on multi-network installs.
 * @param array  $meta    Meta data. Used to set initial site options.
 */
function _wpmu_new_blog( $blog_id, $user_id, $domain, $path, $site_id, $meta ) {
	global $wpdb;

	// ще копира долните таблици от wp_2_* в новия сайт. И ще смени разни стойности като домейна
	$copied_tables_suffixes = ['_options', '_termmeta', '_term_relationships', '_terms', '_term_taxonomy', '_postmeta', '_posts'];
	$source_tables_prefix = 'wp_5';
	$target_tables_prefix = 'wp_' . (int)$blog_id;

	foreach ($copied_tables_suffixes as $table_suffix) {
		$source_table_name = $source_tables_prefix . $table_suffix;
		$target_table_name = $target_tables_prefix . $table_suffix;

		$wpdb->query('TRUNCATE ' . $target_table_name);

		$insert_sql = 'INSERT INTO ' . $target_table_name . ' VALUES ';

		$rows = $wpdb->get_results('SELECT * FROM ' . $source_table_name, ARRAY_N);
		
		foreach ($rows as $row_i => $row) {
			$insert_sql .= ' ( ';
			foreach ($row as $column_i => $column) {
				$insert_sql .= is_numeric($column) ? $column : '"' . esc_sql(replace_old_table_values($column, $domain)) . '"';
				if ($column_i < count($row) - 1) {
					$insert_sql .= ', ';
				}
			}
			$insert_sql .= ' )';

			if ($row_i < count($rows) - 1) {
				$insert_sql .= ', ';
			}
		}

		$wpdb->query($insert_sql);
	}

	// copy all _woocommerce* tables
	// по принцип тия таблици ги създава на woocommerce setup-a, но понеже копирахме горните таблици, setup-a няма да се извика. Затова ги създаваме и копираме ръчно.
	$tables = $wpdb->get_results('show tables', ARRAY_N);
	foreach ($tables as $row) {
		$table = $row[0];
		if (stristr($table, $source_tables_prefix . '_woocommerce_') || stristr($table, $source_tables_prefix . '_wc_')) {
			$source_table = $table;
			$target_table = str_replace($source_tables_prefix, $target_tables_prefix, $source_table);

			$wpdb->query('CREATE TABLE ' . $target_table . ' LIKE ' . $source_table);
			$wpdb->query('INSERT INTO ' . $target_table . ' SELECT * FROM ' . $source_table);
		}
	}

	// в wp_options има поле wp_X_user_roles - този префикс също трябва да се реплейсне с правилния
	$wpdb->query("UPDATE " . $target_tables_prefix . "_options SET option_name = REPLACE(option_name, '" . $source_tables_prefix . "_', '" . $target_tables_prefix . "_')");
}


function replace_old_table_values($value, $domain) {
	$value = str_replace('lyousy.yanaksoft.net', $domain, $value);
	$value = str_replace('demo.yanaksoft.net', $domain, $value);
	return $value;
}