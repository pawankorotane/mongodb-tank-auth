<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * User_Autologin
 *
 * This model represents user autologin data. It can be used
 * for user verification when user claims his autologin passport.
 *
 * @package	Tank_auth
 * @author	Ilya Konyukhov (http://konyukhov.com/soft/)
 */
class User_Autologin extends CI_Model
{
	private $table_name			= 'user_autologin';
	private $users_table_name	= 'users';

	function __construct()
	{
		parent::__construct();

		$ci =& get_instance();
		$this->table_name		= $ci->config->item('db_table_prefix', 'tank_auth').$this->table_name;
		$this->users_table_name	= $ci->config->item('db_table_prefix', 'tank_auth').$this->users_table_name;
	}

	/**
	 * Get user data for auto-logged in user.
	 * Return NULL if given key or user ID is invalid.
	 *
	 * @param	int
	 * @param	string
	 * @return	object
	 */
	function get($user_id, $key)
	{
		$this->mongo_db->where($this->table_name.'.user_id', $user_id);
		$this->mongo_db->where($this->table_name.'.key_id', $key);
		$query = $this->mongo_db->get($this->table_name);
		if (count($query) == 1) return $query;
		return NULL;
	}

	/**
	 * Save data for user's autologin
	 *
	 * @param	int
	 * @param	string
	 * @return	bool
	 */
	function set($user_id, $key)
	{
		
		
		$this->mongo_db->insert($this->table_name,  array(
			'user_id'		=> $user_id,
			'key_id'	 	=> $key,
			'user_agent' 	=> substr($this->input->user_agent(), 0, 149),
			'last_ip' 		=> $this->input->ip_address(),
		));
		if($this->mongo_db->add_index($this->table_name, array(''), 1)){
			return TRUE;
		}
		
}

	/**
	 * Delete user's autologin data
	 *
	 * @param	int
	 * @param	string
	 * @return	void
	 */
	function delete($user_id, $key)
	{
		$this->mongo_db->where('user_id', $user_id);
		$this->mongo_db->where('key_id', $key);
		$this->mongo_db->delete($this->table_name);
	}

	/**
	 * Delete all autologin data for given user
	 *
	 * @param	int
	 * @return	void
	 */
	function clear($user_id)
	{
		$this->mongo_db->where('user_id', $user_id);
		$this->mongo_db->delete($this->table_name);
	}

	/**
	 * Purge autologin data for given user and login conditions
	 *
	 * @param	int
	 * @return	void
	 */
	function purge($user_id)
	{
		$this->mongo_db->where('user_id', $user_id);
		$this->mongo_db->where('user_agent', substr($this->input->user_agent(), 0, 149));
		$this->mongo_db->where('last_ip', $this->input->ip_address());
		$this->mongo_db->delete($this->table_name);
	}
}

/* End of file user_autologin.php */
/* Location: ./application/models/auth/user_autologin.php */