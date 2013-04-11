<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Users
 *
 * This model represents user authentication data. It operates the following tables:
 * - user account data,
 * - user profiles
 *
 * @package	Tank_auth
 * @author	Ilya Konyukhov (http://konyukhov.com/soft/)
 */
class Users extends CI_Model
{
	private $table_name			= 'users';			// user accounts
	private $profile_table_name	= 'user_profiles';	// user profiles

	function __construct()
	{
		parent::__construct();

		$ci =& get_instance();
		$this->table_name			= $ci->config->item('db_table_prefix', 'tank_auth').$this->table_name;
		$this->profile_table_name	= $ci->config->item('db_table_prefix', 'tank_auth').$this->profile_table_name;
	}

	/**
	 * Get user record by Id
	 *
	 * @param	int
	 * @param	bool
	 * @return	object
	 */
	function get_user_by_id($user_id, $activated)
	{
	
		$this->mongo_db->where('_id', new MongoId($user_id));
		$this->mongo_db->where('activated', $activated ? 1 : 0);

		$query = $this->mongo_db->get($this->table_name);
		
		if (count($query) == 1) return $this->single_object_row($query);
		return NULL;
	}

	/**
	 * Get user record by login (username or email)
	 *
	 * @param	string
	 * @return	object
	 */
	function get_user_by_login($login)
	{
		
		//$this->mongo_db->where('username', strtolower($login));
		$this->mongo_db->or_where(array('email' => strtolower($login), 'username' => strtolower($login)));
		$this->mongo_db->limit(1);
		$query = $this->mongo_db->get($this->table_name);
		if (count($query) == 1) return $this->single_object_row($query);
		return NULL;
	}

	/**
	 * Get user record by username
	 *
	 * @param	string
	 * @return	object
	 */
	function get_user_by_username($username)
	{
		$this->mongo_db->where('username', strtolower($username));
		$this->mongo_db->limit(1);
		$query = $this->mongo_db->get($this->table_name);
		if (count($query) == 1) return $this->single_object_row($query);
		return NULL;
	}

	/**
	 * Get user record by email
	 *
	 * @param	string
	 * @return	object
	 */
	function get_user_by_email($email)
	{
		
		$this->mongo_db->where('email', strtolower($email));
		$this->mongo_db->limit(1);
		$query = $this->mongo_db->get($this->table_name);
		if (count($query) == 1) return $this->single_object_row($query);
		return NULL;
	}

	/**
	 * Check if username available for registering
	 *
	 * @param	string
	 * @return	bool
	 */
	function is_username_available($username)
	{
		$this->mongo_db->select('1');
		$this->mongo_db->where('username', strtolower($username));

		$query = $this->mongo_db->get($this->table_name);
		return count($query) == 0;
	}

	/**
	 * Check if email available for registering
	 *
	 * @param	string
	 * @return	bool
	 */
	function is_email_available($email)
	{
		
		$this->mongo_db->select('1');
		$this->mongo_db->where('email', strtolower($email));
		$this->mongo_db->or_where(array('new_email' => strtolower($email)));

		$query = $this->mongo_db->get($this->table_name);
		return count($query) == 0;
	}

	/**
	 * Create new user record
	 *
	 * @param	array
	 * @param	bool
	 * @return	array
	 */
	function create_user($data, $activated = TRUE)
	{
		$data['created'] = new MongoDate();
		$data['activated'] = $activated ? 1 : 0;

		$query =  $this->mongo_db->insert($this->table_name, $data);
		if ($query) {
			$user_id = (string)$query;
			if ($activated)	$this->create_profile($user_id);
			return array('user_id' => $user_id);
		}
		return NULL;
	}

	/**
	 * Activate user if activation key is valid.
	 * Can be called for not activated users only.
	 *
	 * @param	int
	 * @param	string
	 * @param	bool
	 * @return	bool
	 */
	function activate_user($user_id, $activation_key, $activate_by_email)
	{
		
		$this->mongo_db->select('1', FALSE);
		$this->mongo_db->where('_id', new MongoId($user_id));
		if ($activate_by_email) {
			$this->mongo_db->where('new_email_key', $activation_key);
		} else {
			$this->mongo_db->where('new_password_key', $activation_key);
		}
		$this->mongo_db->where('activated', 0);
		$query = $this->mongo_db->get($this->table_name);

		if (count($query) == 1) {

			$this->mongo_db->set('activated', 1);
			$this->mongo_db->set('new_email_key', NULL);
			$this->mongo_db->where('_id', new MongoId($user_id));
			$this->mongo_db->update($this->table_name);

			$this->create_profile($user_id);
			return TRUE;
		}
		
		return FALSE;
	}

	/**
	 * Purge table of non-activated users
	 *
	 * @param	int
	 * @return	void
	 */
	function purge_na($expire_period = 172800)
	{
		$time = new MongoDate();
		$this->mongo_db->where('activated', 0);
		$this->mongo_db->where_gt('created', $time->sec - $expire_period);
		$this->mongo_db->delete($this->table_name);
		
		
	}

	/**
	 * Delete user record
	 *
	 * @param	int
	 * @return	bool
	 */
	function delete_user($user_id)
	{
		$this->mongo_db->where('_id', new MongoId($user_id));
		if($this->mongo_db->delete($this->table_name)){
			return TRUE;
		}
		
		return FALSE;
	}

	/**
	 * Set new password key for user.
	 * This key can be used for authentication when resetting user's password.
	 *
	 * @param	int
	 * @param	string
	 * @return	bool
	 */
	function set_password_key($user_id, $new_pass_key)
	{
		$this->mongo_db->set('new_password_key', $new_pass_key);
		$this->mongo_db->set('new_password_requested', new MongoDate());
		$this->mongo_db->where('_id',new MongoId($user_id));
		if($this->mongo_db->update($this->table_name)){
			return TRUE;
		}else{
			return FALSE;
		}
		
	}

	/**
	 * Check if given password key is valid and user is authenticated.
	 *
	 * @param	int
	 * @param	string
	 * @param	int
	 * @return	void
	 */
	function can_reset_password($user_id, $new_pass_key, $expire_period = 900)
	{
		$time = new MongoDate(time());
		$this->mongo_db->select('1', FALSE);
		$this->mongo_db->where('_id', new MongoId($user_id));
		$this->mongo_db->where('new_password_key', $new_pass_key);
		//$this->mongo_db->where_gte('new_password_requested', $time->sec - $expire_period);   //new some kindof algorithem for checking date
		$this->mongo_db->limit(1);
		$query = $this->mongo_db->get($this->table_name);
		return count($query) == 1;
	}

	/**
	 * Change user password if password key is valid and user is authenticated.
	 *
	 * @param	int
	 * @param	string
	 * @param	string
	 * @param	int
	 * @return	bool
	 */
	function reset_password($user_id, $new_pass, $new_pass_key, $expire_period = 900)
	{
		$time = new MongoDate();
		$this->mongo_db->set('password', $new_pass);
		$this->mongo_db->set('new_password_key', NULL);
		$this->mongo_db->set('new_password_requested', NULL);
		$this->mongo_db->where('_id', new MongoId($user_id));
		$this->mongo_db->where('new_password_key', $new_pass_key);
		
	   if($this->mongo_db->update($this->table_name)){
			return TRUE;
		}else{
			return  FALSE;
		}
		
	}

	/**
	 * Change user password
	 *
	 * @param	int
	 * @param	string
	 * @return	bool
	 */
	function change_password($user_id, $new_pass)
	{
		$this->mongo_db->set('password', $new_pass);
		$this->mongo_db->where('_id', new MongoId($user_id));

		if($this->mongo_db->update($this->table_name)){
			return TRUE;
		}else{
			return FALSE;
		}

	}

	/**
	 * Set new email for user (may be activated or not).
	 * The new email cannot be used for login or notification before it is activated.
	 *
	 * @param	int
	 * @param	string
	 * @param	string
	 * @param	bool
	 * @return	bool
	 */
	function set_new_email($user_id, $new_email, $new_email_key, $activated)
	{
		
		
		$this->mongo_db->set($activated ? 'new_email' : 'email', $new_email);
		$this->mongo_db->set('new_email', $new_email);
		$this->mongo_db->set('new_email_key', $new_email_key);
		$this->mongo_db->where('_id', new MongoId($user_id));
		$this->mongo_db->where('activated', $activated ? 1 : 0);
	
		if($this->mongo_db->update($this->table_name)){
			return  TRUE;
		}else{
			return  FALSE;
		}
		
	}

	/**
	 * Activate new email (replace old email with new one) if activation key is valid.
	 *
	 * @param	int
	 * @param	string
	 * @return	bool
	 */
	function activate_new_email($user_id, $new_email_key)
	{
		$user  =  $this->get_user_by_id($user_id,  TRUE);
		
		$this->mongo_db->set('email', $user->new_email);
		$this->mongo_db->set('new_email', NULL);
		$this->mongo_db->set('new_email_key', NULL);
		$this->mongo_db->where('_id', new MongoId($user_id));
		$this->mongo_db->where('new_email_key', $new_email_key);

		if($this->mongo_db->update($this->table_name)){
			return  TRUE;
		}else{
			return  FALSE;
		}
		
	}

	/**
	 * Update user login info, such as IP-address or login time, and
	 * clear previously generated (but not activated) passwords.
	 *
	 * @param	int
	 * @param	bool
	 * @param	bool
	 * @return	void
	 */
	function update_login_info($user_id, $record_ip, $record_time)
	{
		$this->mongo_db->set('new_password_key', NULL);
		$this->mongo_db->set('new_password_requested', NULL);

		if ($record_ip)		$this->mongo_db->set('last_ip', $this->input->ip_address());
		if ($record_time)	$this->mongo_db->set('last_login', new MongoDate());

		$this->mongo_db->where('_id', new MongoId($user_id));
		$this->mongo_db->update($this->table_name);
	}

	/**
	 * Ban user
	 *
	 * @param	int
	 * @param	string
	 * @return	void
	 */
	function ban_user($user_id, $reason = NULL)
	{
		$this->mongo_db->where('_id', new MongoId($user_id));
		$this->mongo_db->set(array(
			'banned'		=> 1,
			'ban_reason'	=> $reason,
		));
		$this->mongo_db->update($this->table_name);
	}

	/**
	 * Unban user
	 *
	 * @param	int
	 * @return	void
	 */
	function unban_user($user_id)
	{
		$this->mongo_db->where('_id', new MongoId($user_id));
		$this->mongo_db->set(array(
		'banned'		=> 0,
		'ban_reason'	=> NULL,
		));
		$this->mongo_db->update($this->table_name);
	}

	/**
	 * Create an empty profile for a new user
	 *
	 * @param	int
	 * @return	bool
	 */
	private function create_profile($user_id)
	{
		$this->mongo_db->where('_id',  new MongoId($user_id));
		$this->mongo_db->set('profile',  array('user_id' => new MongoId($user_id)));
		$this->mongo_db->update('users');
		$this->mongo_db->add_index('users',array('profile' =>  array('user_id')), 1);   // Add an index to profile table
		

}

	/**
	 * Delete user profile
	 *
	 * @param	int
	 * @return	void
	 */
	private function delete_profile($user_id)
	{
		$this->mongo_db->where('_id', new MongoId($user_id));
		$this->mongo_db->delete('users');
		
	}
	
	
	/**
	* 
	* Creates Single Object Row 
	* @param array 
	* 
	*/
	
	
	function single_object_row($array = array()){
		if(is_array($array)){
			$result = (object)array_shift($array);
			return $result;
		}else{
			return FALSE;
		}
	}
	
}

/* End of file users.php */
/* Location: ./application/models/auth/users.php */