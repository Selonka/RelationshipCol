<?php


class RelationshipCountColumnRelated extends MantisColumn {

	public $column = "RelationshipCountRelated";
	public $sortable = TRUE;

	private $cache = array();

	public function __construct() {
		plugin_push_current( 'RelationshipCol' );

		$this->title = plugin_lang_get( 'col_related' );
		plugin_pop_current();
	}

	public function cache( array $p_bugs ) {
		if ( count( $p_bugs ) < 1 ) {
			return;
		}
		$t_rcount_table = db_get_table( 'plugin_relationshipcol_relationshipcount' );
		$t_bug_ids = array();
		foreach ( $p_bugs as $t_bug ) {
			$t_bug_ids[] = $t_bug->id;
		}

		$t_bug_ids = implode( ',', $t_bug_ids );

		$t_query = "SELECT * FROM $t_rcount_table WHERE bugId IN ( $t_bug_ids )";
		$t_result = db_query( $t_query );

		while ( $t_row = db_fetch_array( $t_result ) ) {
				if ( !isset( $this->cache[ $t_row['bugid'] ] ) ) {
					$this->cache[ $t_row['bugid'] ] =  $t_row['countrelated'];
			}
		}


	}

	public function display( BugData $p_bug, $p_columns_target ) {
		plugin_push_current( 'RelationshipCol' );

		if ( isset( $this->cache[ $p_bug->id ] ) ) {
			echo '<a href="view.php?id=', $p_bug->id, '#changesets">', $this->cache[ $p_bug->id ], '</a>';
		}

		plugin_pop_current();
	}
	
	public function sortquery( $p_dir ) {
		
		$t_bug_table = db_get_table( 'bug' );
		$t_rcount_table = db_get_table( 'plugin_relationshipcol_relationshipcount' );
		
		return array(
			'join' => "LEFT JOIN $t_rcount_table rcount ON $t_bug_table.id=rcount.bugId",
			'order' => "rcount.countrelated $p_dir",
		);
	}
}
