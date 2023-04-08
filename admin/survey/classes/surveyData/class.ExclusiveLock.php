<?php 

class ExclusiveLock
{
	protected $key;  	            // Lock key
	protected $own = false; 	    // Owner of lock

    
	public function __construct($key=NULL) {

		if ($key == NULL) {
			throw new Exception('ExclusiveLock: Key must not be NULL!');
        }
        
        $this->key = $key;
    }
    
    function __destruct() {

		if($this->own == TRUE) {
			$this->unlock();
		}
	}


    // Poskusamo zakleniti datoteke za doloceno anketo
	public function lock() {
		global $global_user_id;
		
		// Preverimo ce je datoteka zaklenjena in če je od tega minilo manj kot 15 minut
		$sqlLocked = sisplet_query("SELECT COUNT(*) 
                                    FROM srv_lock 
                                    WHERE lock_key = '$this->key' AND locked='1' AND last_lock_date > NOW() - INTERVAL 15 MINUTE");
		list($lockCount) = mysqli_fetch_row($sqlLocked);
		
        // Datoteka je zaklenjena - mi nimamo dostopa do nje
		if ($lockCount > 0){
            $this->own = false;
        }
        // Datoteka je odklenjena - jo zaklenemo
        else{
            $sqlLock = sisplet_query("INSERT INTO srv_lock 
                                            (lock_key, locked, usr_id, last_lock_date) 
                                        VALUES 
                                            ('".$this->key."', '1', '".$global_user_id."', NOW()) 
                                        ON DUPLICATE KEY 
                                            UPDATE locked='1', usr_id='".$global_user_id."', last_lock_date=NOW()");

            // Uspesno smo zaklenili datoteko - nastavimo, da smo owner in lahko operiramo z datoteko
            if ($sqlLock) {
                $this->own = true;
            }
        }

        return $this->own;
	}

    // Odklenemo datoteko, ki jo generiramo
	public function unlock() {
        
        // Ce smo owner lahko odkelenmo datoteke za anketo
        if($this->own){
        
            // Updejtamo lock = '0' in popravimo datum odklepanja
		    $sqlUnlock = sisplet_query("UPDATE srv_lock SET locked='0', last_unlock_date=NOW() WHERE lock_key='".$this->key."'");
        }

        // Po odklepanju nismo vec owner
        $this->own = false;
	}
    
    // Pridobimo datum zadnjega zaklepanja
	function getLockDate(){

        $sql = sisplet_query("SELECT DATE_FORMAT(last_lock_date, '%m.%d.%Y  %T') FROM srv_lock WHERE lock_key='".$this->key."' AND locked='1'");
        list($lastLockDate) = mysqli_fetch_row($sql);
        
		return $lastLockDate;
	}
};