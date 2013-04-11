<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Login_attempts
 *
 * This model serves to watch on all attempts to login on the site
 * (to protect the site from brute-force attack to user database)
 *
 * @package	Tank_auth
 * @author	Ilya Konyukhov (http://konyukhov.com/soft/)
 */
class Login_attempts extends CI_Model
{
	private $table_name = 'login_attempts';

	function __construct()
	{
		parent::__construct();

		$ci =& get_instance();
		$this->table_name = $ci->config->item('db_table_prefix', 'tank_auth').$this->table_name;
	}

	/**
	 * Get number of attempts to login occured from given IP-address or login
	 *
	 * @param	string
	 * @param	string
	 * @return	int
	 */
	function get_attempts_num($ip_address, $login)
	{
		$this->mongo_db->select('1',  FALSE);
		$this->mongo_db->where('ip_address', $ip_address);
		if (strlen($login) > 0) $this->mongo_db->or_where(array('login' =>  $login));

		$qres = $this->mongo_db->get($this->table_name);
		return count($qres);
	}

	/**
	 * Increase number of attempts for given IP-address and login
	 *
	 * @param	string
	 * @param	string
	 * @return	void
	 */
	function increase_attempt($ip_address, $login)
	{
		$this->mongo_db->insert($this->table_name, array('ip_address' => $ip_address, 'login' => $login));
	}

	/**
	 * Clear all attempt records for given IP-address and login.
	 * Also purge obsolete login attempts (to keep DB clear).
	 *
	 * @param	string
	 * @param	string
	 * @param	int
	 * @return	void
	 */
	function clear_attempts($ip_address, $login, $expire_period = 86400)
	{
		$this->mongo_db->where(array('ip_address' => $ip_address, 'login' => $login));

		// Purge obsolete login attempts
		$mongodate = new MongoDate();
		$this->mongo_db->where_lte('time', $mongodate->sec - $expire_period);
		$this->mongo_db->delete($this->table_name);
	}
}

/* End of file login_attempts.php */
/* Location: ./application/models/auth/login_attempts.php */