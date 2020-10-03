# RelationshipCol
A Mantis Plugin to show the relationshipcount on the filterpage.
  
## Description ##   
This plugin for Mantis allows you to display 3 additional columns (sortable) in the View Issue Page to show the count of relationships for a ticket.  

* [ RC Parent of ] The number of tickets on which the current ticket is dependent  
* [ RC Child of ] The number of tickets that are blocked by the current ticket
* [ RC Realated ] The number of tickets related to the current ticket
  
![Screenshot of view issue page, slimmed down](https://github.com/Selonka/RelationshipCol/blob/main/blob/RelationCol.PNG)

## Requirements ##
* Mantis v2.x.x
* Tested on 2.24.0

## Installation ##

Before the installation a little preparation is necessary, the files events_inc.php and relationship_api.php in the Mantis Core folder must be replaced by the files in the Manipulated Core Files. Before this happens a backup of the files should be made.  

Alternatively the following lines can be added to relationship_api.php:  

event_signal( 'EVENT_RELATIONSHIP_ADDED', $t_relationship_id );  
After the query for adding a relationship has been executed (~ line 253).   
  
event_signal( 'EVENT_RELATIONSHIP_DELETE', $p_relationship_id );  
Before the query to delete the relationship was executed (~ line 339)  
  
In the events_inc.php the following lines must be introduced:  
	'EVENT_RELATIONSHIP_ADDED' => EVENT_TYPE_EXECUTE,  
	'EVENT_RELATIONSHIP_DELETE' => EVENT_TYPE_EXECUTE,  
  
Under # Other bug events  
  
Copy the plugin folder under /mantis/plugins and install the plugin via Manage Plugins in the administration
## Known issues ##
 * Nothing here yet feel free to report ;)

## Next development steps ##
 * Refactoring
 * Add other language
 * Prepare Files for other Version of Mantis too
## Disclaimer ##
Any use of this plugin is at your own risk. We are not responsible for any damage or data loss incurred with his use.

## Thanks to ##
Translated with www.DeepL.com/Translator (free version)