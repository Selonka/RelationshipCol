<?php

  /** Plugin declaration
   * extends MantisPlugin
   * Example plugin that implements Jquery files
   */

class RelationshipColPlugin extends MantisPlugin  
 {
## Register
 function register()
    {
      $this->name = 'RelationshipCol';
      $this->description = 'Show Relationship count columns';

      $this->version = '0.0.1';
      $this->requires = array(
        "MantisCore" => "2.0.0",
      );

      $this->author = 'Selonka';
      $this->contact = '';
      $this->url = 'https://github.com/Selonka/RelationshipCol';
    }
 
    function init()
	{
    if(! plugin_config_get("is_table_init")){
      if(plugin_config_get("schema")){
          plugin_config_set("is_table_init", true);
          UpdateTables();
      }
    }
  }
  
## Events
function events(){
  return array(
  'EVENT_RELATIONSHIP_ADDED' => EVENT_TYPE_EXECUTE,
  'EVENT_RELATIONSHIP_DELETE' => EVENT_TYPE_EXECUTE,
  );
}

## Config
 function config() 
 {
  return array(
			'view_threshold'	=> VIEWER,
			'enable_porting'	=> OFF,
      'manage_threshold'	=> ADMINISTRATOR,
      'is_table_init'  => false
		);
	}
## Hooks 
  function hooks()
  {
    return array(      
    "EVENT_RELATIONSHIP_ADDED" => 'updaterelationshipsAdd',
    "EVENT_RELATIONSHIP_DELETE" => 'updaterelationshipsDelete',
    "EVENT_FILTER_COLUMNS" => 'filtercolumns',
    "EVENT_UPDATE_BUG" => 'updateBugData'
    );
  }

## Schema
  function schema() 
  {
      $t_db_tables_array = createTables();
      return array_merge(array(
      array( "CreateTableSQL", array( plugin_table( "relationshipcount" ), "
        id			I		NOTNULL UNSIGNED AUTOINCREMENT PRIMARY,
        bugId		I		NOTNULL,
        countParent		I		NOTNULL,
        countChild		I		NOTNULL,
        countRelated		I		NOTNULL
        ")),
        array( "mysql" => "DEFAULT CHARSET=utf8" )
      ) , $t_db_tables_array);
  }

  function filtercolumns(){
    require_once( 'classes/RelationshipCountColumnParent.class.php' );
    require_once( 'classes/RelationshipCountColumnChild.class.php' );
    require_once( 'classes/RelationshipCountColumnRelated.class.php' );
		return array(
      'RelationshipCountColumnParent',
      'RelationshipCountColumnChild',
      'RelationshipCountColumnRelated'
		);
  }

  function updaterelationshipsAdd($p_event, $p_relation_id){
    updaterelationships($p_relation_id, "+");
  }

  function updaterelationshipsDelete($p_event, $p_relation_id){
    updaterelationships($p_relation_id, "-");
  }

  function updateBugData($p_issue_id, $p_old_bug_data, $p_new_bug_data){
    $t_old_bug_status = $p_old_bug_data -> status;
    $t_new_bug_status = $p_new_bug_data -> status;
    $t_bug_Id = $p_new_bug_data -> id;
  
    //Status changed to resolved or closed Remove from Count
    if($t_new_bug_status >= 80 AND $t_old_bug_status < 80){
      updaterealtionshipsParent( $t_bug_Id, "-");
    }

    //Status changed from resolved/closed to other
    if($t_new_bug_status < 80 AND $t_old_bug_status >= 80){
      updaterealtionshipsParent( $t_bug_Id, "+");
    }
  }
}

function createTables(){
  
   $t_query = "SELECT * FROM {bug_relationship} AS relations INNER JOIN {bug} as bugTable ON relations.source_bug_id = bugTable.id" ;
   $t_result = db_query( $t_query );
    
   $t_count_arrays = countRelationShips($t_result, null);
   $t_count_array_blocks = $t_count_arrays["block_array"];
   $t_count_array_depends = $t_count_arrays["depend_array"];
   $t_count_array_related = $t_count_arrays["related_array"];
   $t_bugId_list = $t_count_arrays["bug_list"];
   
   
   $t_return_array = array();
   // Create Records in DB
   foreach ( $t_bugId_list as $t_bugId) {
     //blocks
     if ( isset( $t_count_array_blocks[$t_bugId ] ) ) {
       $t_count_blocks = $t_count_array_blocks[$t_bugId];
     }else{
       $t_count_blocks = 0;
     }
   
     //depends
     if ( isset( $t_count_array_depends[ $t_bugId ] ) ) {
       $t_count_depends = $t_count_array_depends[$t_bugId];
     }else{
       $t_count_depends = 0;
     }
   
     //related
     if ( isset( $t_count_array_related[ $t_bugId] ) ) {
       $t_count_related = $t_count_array_related[$t_bugId];
     }else{
       $t_count_related = 0;
     }
     $t_return_array[] = array( 'InsertData', array( plugin_table( "relationshipcount" ), 
     " (bugId, countParent, countChild, countRelated) VALUES (" . $t_bugId . "," . $t_count_depends . "," . $t_count_blocks . "," . $t_count_related . ")"
    ));
  
   }
   return $t_return_array;
 }

function UpdateTables(){
 $t_rcount_table = plugin_table( 'relationshipcount' );
 
  $t_query = "SELECT * FROM {bug_relationship} AS relations INNER JOIN {bug} as bugTable ON relations.destination_bug_id = bugTable.id" ;
  $t_result = db_query( $t_query );
  $t_bugId_list = array();

  while ( $t_row = db_fetch_array( $t_result ) ) {
    $t_bug_status = $t_row["status"];
    $t_bug_Id = $t_row["id"];
    
    if (!isset( $t_bugId_list[$t_bug_Id])){
      $t_bugId_list[$t_bug_Id] = "$t_bug_Id";
      if($t_bug_status >= 80){
        $t_db_array[] = updaterealtionshipsParent( $t_bug_Id, "-" );
      }
    } 
  }
 
}

function countRelationShips($p_dbResults){
  
  $t_count_array_blocks = array();
  $t_count_array_depends = array();
  $t_count_array_related = array();
  $t_bugId_list = array();

  while ( $t_row = db_fetch_array( $p_dbResults ) ) {
    $t_bugId = $t_row["id"];
    $t_bugStatus = $t_row["status"];

    if (!isset( $t_bugId_list[$t_bugId])){
      $t_bugId_list[$t_bugId] = "$t_bugId";
    }
     
      if($t_row['relationship_type'] == "2"){
        //depends
        if ( isset($t_count_array_depends [ $t_row['source_bug_id'] ] ) ) {
          $t_count_array_depends [ $t_row['source_bug_id'] ]++;
        } else {
          $t_count_array_depends [ $t_row['source_bug_id'] ] = 1;
        }

        //blocks
        if ( isset($t_count_array_blocks [ $t_row['destination_bug_id'] ] ) ) {
          $t_count_array_blocks [ $t_row['destination_bug_id'] ]++;
        } else {
          $t_count_array_blocks [ $t_row['destination_bug_id'] ] = 1;
        }
        //Add BlockId too
        if (!isset( $t_bugId_list[$t_row['destination_bug_id']])){
          $t_bugId_list[$t_row['destination_bug_id']] = $t_row['destination_bug_id'];
        }
     } 
    
    if($t_row['relationship_type'] == "1"){
      //Source
      if ( isset($t_count_array_related [ $t_row['source_bug_id'] ] ) ) {
        $t_count_array_related [ $t_row['source_bug_id'] ]++;

      } else {
        $t_count_array_related [ $t_row['source_bug_id'] ] = 1;
      }

      //Destination
      if ( isset($t_count_array_related [ $t_row['destination_bug_id'] ] ) ) {
        $t_count_array_related [ $t_row['destination_bug_id'] ]++;

      } else {
        $t_count_array_related [ $t_row['destination_bug_id'] ] = 1;
      }

      //add Destination Bug for related too
      if (!isset( $t_bugId_list[$t_row['destination_bug_id']])){
        $t_bugId_list[$t_row['destination_bug_id']] = $t_row['destination_bug_id'];
      }
    }
  }
  return array(
    "block_array" => $t_count_array_blocks,
    "depend_array" => $t_count_array_depends,
    "related_array" => $t_count_array_related,
    "bug_list" =>  $t_bugId_list
  );
}

function updaterealtionshipsParent($p_bug_Id, $p_sign){
  
  db_param_push();
  $t_query = "SELECT * FROM {bug_relationship} WHERE destination_bug_id = " . db_param();
  $t_dbresults = db_query( $t_query, array($p_bug_Id) );
  $t_db_array = array();
    while ( $t_row = db_fetch_array( $t_dbresults ) ) {
      updaterelationships($t_row["id"], $p_sign, $p_withrelation = false);
    }
}

function updaterelationships($p_relation_id, $p_sign, $p_withrelation = true){
  db_param_push();
  $t_query = "SELECT * FROM {bug_relationship} WHERE id IN ( ". db_param() . ")";
  $t_result = db_query( $t_query, array($p_relation_id) );

  $t_row = db_fetch_array( $t_result ); 
  $t_source_bug_id = $t_row["source_bug_id"];
  $t_destination_bug_id = $t_row["destination_bug_id"];
  $t_relationship_type = $t_row["relationship_type"];


  if($t_relationship_type == "2"){
    //Depends
   UpdateDB($t_source_bug_id, "countParent", $p_sign);
    //Blocks
  UpdateDB($t_destination_bug_id, "countChild", $p_sign);
  }

  if($p_withrelation){
    if($t_relationship_type == "1"){
      UpdateDB($t_source_bug_id, "countRelated", $p_sign);
      UpdateDB($t_destination_bug_id, "countRelated", $p_sign);
    }
  }
}

function UpdateDB($p_bugId, $p_count_type, $p_sign){
  $t_rcount_table = plugin_table( 'relationshipcount' );
  db_param_push();
  $t_query = "SELECT * FROM $t_rcount_table WHERE bugId =" . db_param();
  $t_result = db_query( $t_query, array($p_bugId) );
  $t_update = false;
  //Check if Bug has an Entry in Table
  
  if($t_row = db_fetch_array($t_result)){
    $t_update  = true;
  }
  
  if($p_count_type == "countParent"){
    if($t_update){
      if($p_sign == "-"){
        $t_query = "UPDATE $t_rcount_table SET countParent = countParent - 1 WHERE bugId = " . db_param();
      }else{
        $t_query = "UPDATE $t_rcount_table SET countParent = countParent + 1 WHERE bugId = " . db_param();
      }
    }else{
      $t_query = "INSERT INTO $t_rcount_table ( bugId, countParent, countChild, countRelated ) VALUES (" . db_param() . ", 1,0,0)";
    }
  }
    
  if($p_count_type == "countChild"){
    if($t_update){
      if($p_sign == "-"){
        $t_query = "UPDATE $t_rcount_table SET countChild = countChild - 1 WHERE bugId = " . db_param();
      }else{
        $t_query = "UPDATE $t_rcount_table SET countChild = countChild + 1 WHERE bugId = " . db_param();
      }
    }else{
      $t_query = "INSERT INTO $t_rcount_table ( bugId, countParent, countChild, countRelated ) VALUES (" . db_param() . ", 0,1,0)";
    }
  }
    
  if($p_count_type == "countRelated"){
    if($t_update){
      if($p_sign == "-"){
        $t_query = "UPDATE $t_rcount_table SET countRelated = countRelated - 1 WHERE bugId = " . db_param();
      }else{
        $t_query = "UPDATE $t_rcount_table SET countRelated = countRelated + 1 WHERE bugId = " . db_param();
      }
    }else{ 
      $t_query = "INSERT INTO $t_rcount_table ( bugId, countParent, countChild, countRelated ) VALUES (" . db_param() . ", 0,0,1)";
    }
  }
  db_param_push();
  db_query( $t_query, array($p_bugId) );
}
