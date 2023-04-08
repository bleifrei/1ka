<?php 
	
	include_once '../../function.php';
    
    include_once 'classes/class.DisplayCheck.php';
    include_once 'classes/class.DisplaySettings.php';
    include_once 'classes/class.ImportDB.php';
    include_once 'classes/class.DisplayDatabase.php';
    

	// Poslana zahteva za izbris
	if($_GET['a'] == 'submit_settings'){

        $ds = new DisplaySettings();
        $ds->ajaxSubmitSettings();
    }
    
    // Izvedemo uvoz celotne baze
	if($_GET['a'] == 'import_database'){

        $db = new ImportDB();
        $db->executeImport();
    }
    
    // Izvedemo posodobitev baze
	if($_GET['a'] == 'update_database'){

        $db = new ImportDB();
        $db->executeUpdate();
	}
