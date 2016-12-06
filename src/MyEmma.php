<?php
namespace Advisantgroup;
require_once 'lib/Emma/Emma.php';
/**
* Emma Methods
*/
class Myemma
{
	protected $emmaObj;
	protected $account_id;
	protected $public_key;
	protected $private_key;
	
	function __construct($credential='')
	{
		if(!$credential || !is_array($credential) || !isset($credential['account_id']) || !isset($credential['public_key']) || !isset($credential['private_key']))
        {
            return false;
        }

		$this->account_id = $credential['account_id'];
		$this->public_key = $credential['public_key'];
		$this->private_key = $credential['private_key'];
	}

	public function set_emma()
	{
		$this->emmaObj = '';
		$this->emmaObj = new Emma($this->account_id, $this->public_key, $this->private_key);
	}

	public function list_member()
	{
		try
		{
			$this->set_emma();
			$req = $this->emmaObj->myMembers();
			return $req;
		}
		catch(Emma_Invalid_Response_Exception $e)
		{
		    // return $e->getMessage();
			return false;
		}
	}

	public function add_single_member($member_data='',$member_group_id='')
	{
		if(!$member_data || !is_array($member_data) || !isset($member_data['emma_mage_email']))
        {
            return false;
        }

        $member_array = array();

    	$member_array['email'] = $member_data['emma_mage_email'];

		$member_array['fields'] = array();

    	foreach ($member_data as $key => $value)
    	{
    		if($key=='emma_mage_email')
    			continue;

    		$member_array['fields'][$key] = $value;
    	}

        if(count($member_array)<1)
        	return false;

		try
		{
			$my_member = array();
		    $my_member['email'] = $member_array['email'];
		    
		    if($member_group_id)
		    	$my_member['group_ids'] = array((int)$member_group_id);
		    
		    $my_member['fields'] = $member_array['fields'];

			$this->set_emma();
		    $req = $this->emmaObj->membersAddSingle($my_member);
		    return $req;
		}
		catch(Emma_Invalid_Response_Exception $e)
		{
		    return false;
		}
	}

	public function update_member_email($old_email='', $new_email='')
	{
		if(!$new_email || !filter_var($new_email, FILTER_VALIDATE_EMAIL) || !$old_email || !filter_var($old_email, FILTER_VALIDATE_EMAIL))
        {
            return false;
        }

        $member_data = $this->list_member_by_email($old_email);

        if(!$member_data)
        {
        	return false;
        }

        $member_data = json_decode($member_data);

		try
		{
			$my_member = array();
		    $my_member['email'] = $new_email;

			$this->set_emma();
		    $req = $this->emmaObj->membersUpdateSingle($member_data->member_id, $my_member);
		    return $req;
		}
		catch(Emma_Invalid_Response_Exception $e)
		{
		    return false;
		}
	}

	public function members_batch_add($member_data = '',$member_group_id='')
	{
		if(!$member_data || !is_array($member_data))
        {
            return false;
        }

        $emma_member = array();

        foreach ($member_data as $member)
        {
        	$member_child_array = array();

        	$member_child_array['email'] = $member['emma_mage_email'];

			$member_child_array['fields'] = array();

        	foreach ($member as $key => $value)
        	{
        		if($key=='emma_mage_email')
        			continue;

        		$member_child_array['fields'][$key] = $value;
        	}

        	$emma_member[] = $member_child_array;
        }

        // return $emma_member;

		try
		{
			$my_member = array(
				'members' => $emma_member
			);

			if($member_group_id)
			{
				$my_member['group_ids'] = array((int)$member_group_id);
			}

			$this->set_emma();
		    $req = $this->emmaObj->membersBatchAdd($my_member);
		    return $req;
		}
		catch(Emma_Invalid_Response_Exception $e)
		{
		    return false;
		}
	}

	public function members_imported($import_id='')
	{
		if(!$import_id)
			return false;

		try
		{
			$this->set_emma();
		    $req = $this->emmaObj->membersImported($import_id);
		    return $req;
		}
		catch(Emma_Invalid_Response_Exception $e)
		{
		    return false;
		}
	}

	public function add_members_to_group($emails='',$group_id='')
	{
		$emails = array_filter($emails);

		if(!$emails || !$group_id || !is_array($emails))
		{
			return false;
		}

		try
		{
			$member = array();
		    $member['members'] = $emails;
		    $member['group_ids'] = array((int)$group_id);
			$this->set_emma();
		    $req = $this->emmaObj->membersBatchAdd($member);
		    return $req;
		}
		catch(Emma_Invalid_Response_Exception $e)
		{
		    return false;
		}
	}

	public function list_member_by_email($email='')
	{
		if(!$email)
		{
			return false;
		}

		try
		{
			$this->set_emma();
			$req = $this->emmaObj->membersListByEmail($email);
			return $req;
		}
		catch(Emma_Invalid_Response_Exception $e)
		{
		    return false;
		}
	}

	public function list_fields($include_default_fields = false)
	{
		try
		{
			$this->set_emma();
			$req = $this->emmaObj->myFields();
			
			if($include_default_fields===true)
				return $req;

			return  $this->remove_default_fields($req);
		}
		catch(Emma_Invalid_Response_Exception $e)
		{
		    return false;
		}
	}

	public function add_field($field_data = '')
	{
		if(!$field_data || !is_array($field_data) || !isset($field_data['display_name']) || !isset($field_data['widget_type']))
        {
            return false;
        }

		$widget_to_field = array(
			'text' => 'text',
			'long' => 'text[]',
			'number' => 'numeric',
			'date' => 'date',
			'radio' => 'boolean',
			'checkbox' => 'boolean',
			'check_multiple' => 'text[]',
			'select_one' => 'text',
			'select_multiple' => 'text[]'
		);

		if(!array_key_exists($field_data['widget_type'], $widget_to_field))
		{
			return false;
		}
		
		$shortcut_name = $this->_sanitize_string($field_data['display_name']);

		try
		{
			if(!isset($field_data['field_type']))
				$data['field_type'] = $widget_to_field[$field_data['widget_type']];
			else
				$data['field_type'] = $field_data['field_type'];

			$data['shortcut_name'] = isset($field_data['shortcut_name']) ? $field_data['shortcut_name'] : $shortcut_name;
			$data['display_name'] = $field_data['display_name'];
			$data['widget_type'] = $field_data['widget_type'];
			$data['column_order'] = 100;

			$response = array();
			$this->set_emma();
			$req = $this->emmaObj->fieldsAddSingle($data);

			$response['id'] = $req;
			$response['display_name'] = $data['display_name'];
			$response['shortcut_name'] = $data['shortcut_name'];

			return $response;
		}
		catch(Emma_Invalid_Response_Exception $e)
		{
		    return false;
		}
	}

	public function list_groups($include_default_groups = false)
	{
		try
		{
			$this->set_emma();
			$req = $this->emmaObj->myGroups();
			
			if($include_default_groups===true)
				return $req;

			return  $this->remove_default_groups($req);
		}
		catch(Emma_Invalid_Response_Exception $e)
		{
		    return false;
		}
	}

	public function add_group($group_name='')
	{
		if(!$group_name) return false;
		
		try 
		{
		    $group = array();
		    $group = array('groups' => array(array('group_name' => $group_name)));
		    $this->set_emma();
		    $req = $this->emmaObj->groupsAdd($group);
		    return $req;
		}
		catch(Emma_Invalid_Response_Exception $e)
		{
		    return false;
		}
	}

	public function list_searches($include_default_searches = false)
	{
		try
		{
			$response = array();
			$this->set_emma();
			$req = $this->emmaObj->mySearches();

			if($include_default_searches===true)
				return $req;

			return  $this->remove_default_searches($req);
		}
		catch(Emma_Invalid_Response_Exception $e)
		{
		    return false;
		}
	}

	public function add_search($search_data = '')
	{
		if(!isset($search_data['name']) || !isset($search_data['criteria']))
		{
			return false;
		}

		try
		{
			$response = array();
			$this->set_emma();
			$req = $this->emmaObj->searchesCreateSingle($search_data);

			return $req;
		}
		catch(Emma_Invalid_Response_Exception $e)
		{
		    return false;
		}
	}

	public function has_access_to_event_api()
	{
		$this->set_emma();
		$resp = $this->emmaObj->hasEventAccess();
		
		if(!$resp)
			return false;

		if(strpos($resp, 'success') !== false && strpos($resp, 'true') !== false)
			return true;
			
		return false;
	}

	public function validate_emma_keys()
	{
		$resp = $this->list_fields();

		if($resp)
			return $resp;
		
		return false;
	}

	public function verify_required_emma_fields($my_fields = '',$create_if_not_exists = false)
    {
    	if(!$my_fields)
    	{
    		$my_fields = $this->list_fields(true);
    	}

        if(!$my_fields)
        {
            return false;
        }

        $required_fields = array(
        	'mag-total-orders' => 0,
        	'mag-total-purchased' => 0,
        	'mag-highest-purchase' => 0,
        	'mag-avg-purchase' => 0,
        	'mag-last-purchase' => 0
    	);

        foreach ($required_fields as $req_field => $value)
        {
        	$is_exists = $this->is_field_exists('shortcut_name', $req_field, $my_fields);
        	
        	if($is_exists)
        	{
        		$required_fields[$req_field] = 1;
        	}
        	else
        	{
        		$required_fields[$req_field] = 0;
        	}
        }

        if(!$create_if_not_exists)
        {
        	return $required_fields;
        }

        $fields_created = $this->create_required_emma_fields($required_fields);
    	
    	return $fields_created;
    }

	public function verify_required_emma_searches($my_searches = '',$create_if_not_exists = false)
    {
    	if(!$my_searches)
    	{
			$my_searches = $this->list_searches(true);
    	}

        if(!$my_searches)
        {
            return false;
        }

        $req_searches = array(
			"Purchased in last year" => 0,
			"Purchased in last 30 days" => 0,
			"Have more than one total order" => 0,
			"Subscribed, but have not purchased" => 0,
			"Average purchase is greater than $50" => 0,
			"Cumulative purchases are greater than $100" => 0
		);

		foreach ($req_searches as $req_search => $value)
        {
        	$is_exists = $this->is_field_exists('name', $req_search, $my_searches);
        	
        	if($is_exists)
        	{
        		$req_searches[$req_search] = 1;
        	}
        	else
        	{
        		$req_searches[$req_search] = 0;
        	}
        }

        if(!$create_if_not_exists)
        {
        	return $req_searches;
        }

        $search_created = $this->create_required_emma_searches($req_searches);
    	
    	return $search_created;
    }

	public function verify_required_emma_groups($create_if_not_exists = false)
    {
		$my_groups = $this->list_groups(true);

        if(!$my_groups)
        {
            return false;
        }

        $req_group = 'Magento Customers';

    	$is_exists = $this->is_field_exists('group_name', $req_group, $my_groups);

        if(!$create_if_not_exists)
        {
        	return $is_exists;
        }

        if($is_exists)
        	return $is_exists;

        $group_created = $this->add_group($req_group);
    	
    	return $group_created;
    }

    public function create_required_emma_fields($required_fields='')
    {
    	$data = array();

    	$data['mag-total-orders'] =array(
	    	'shortcut_name' => 'mag-total-orders',
			'display_name' => 'Total No of Orders',
			'field_type' => 'numeric',
			'widget_type' => 'number'
		);
		
		$data['mag-total-purchased'] = array(
			'shortcut_name' => 'mag-total-purchased',
			'display_name' => 'Total Purchased (Money Spent)',
			'field_type' => 'numeric',
			'widget_type' => 'number'
		);

		$data['mag-highest-purchase'] = array(
			'shortcut_name' => 'mag-highest-purchase',
			'display_name' => 'Highest Purchase ($)',
			'field_type' => 'numeric',
			'widget_type' => 'number'
		);

		$data['mag-avg-purchase'] = array(
			'shortcut_name' => 'mag-avg-purchase',
			'display_name' => 'Average Purchase ($)',
			'field_type' => 'numeric',
			'widget_type' => 'number'
		);

		$data['mag-last-purchase'] = array(
			'shortcut_name' => 'mag-last-purchase',
			'display_name' => 'Last Purchase Date',
			'field_type' => 'date',
			'widget_type' => 'number'
		);

		foreach ($required_fields as $field_name => $exists)
		{
			if(!$exists)
			{
				$req = $this->add_field($data[$field_name]);

				if(!$req)
				{
					return false;
				}
			}
		}

		return true;
    }

    public function create_required_emma_searches($req_searches='')
    {
    	$data = array();

    	$data['Purchased in last year'] = Array
		(
			'name' => 'Purchased in last year',
		    'criteria' => Array
		    (
			    0 => 'and',
			    1 => Array
		        (
		            0 => 'and',
		            1 => Array
		            (
		                0 => 'member_field:mag-last-purchase',
		                1 => 'in last',
		                2 => Array
		                (
		                    'day' => 365
		                )
		            )
		        )
			)
		);

    	$data['Purchased in last 30 days'] = Array
		(
			'name' => 'Purchased in last 30 days',
		    'criteria' => Array
		    (
			    0 => 'and',
			    1 => Array
		        (
		            0 => 'and',
		            1 => Array
		            (
		                0 => 'member_field:mag-last-purchase',
		                1 => 'in last',
		                2 => Array
		                (
		                    'day' => 30
		                )
		            )
		        )
			)
		);

    	$data['Have more than one total order'] = Array
		(
			'name' => 'Have more than one total order',
		    'criteria' => Array
		    (
			    0 => 'and',
			    1 => Array
		        (
		            0 => 'and',
		            1 => Array
		            (
		                0 => 'member_field:mag-total-orders',
		                1 => 'gt',
		                2 => 1
		            )
		        )
			)
		);

    	$data['Subscribed, but have not purchased'] = Array
		(
			'name' => 'Subscribed, but have not purchased',
		    'criteria' => Array
		    (
			    0 => 'and',
			    1 => Array
		        (
		            0 => 'and',
		            1 => Array
		            (
		                0 => 'member_field:mag-total-orders',
		                1 => 'lt',
		                2 => 1
		            )
		        )
			)
		);

    	$data['Average purchase is greater than $50'] = Array
		(
			'name' => 'Average purchase is greater than $50',
		    'criteria' => Array
		    (
			    0 => 'and',
			    1 => Array
		        (
		            0 => 'and',
		            1 => Array
		            (
		                0 => 'member_field:mag-avg-purchase',
		                1 => 'gt',
		                2 => 50
		            )
		        )
			)
		);

    	$data['Cumulative purchases are greater than $100'] = Array
		(
			'name' => 'Cumulative purchases are greater than $100',
		    'criteria' => Array
		    (
			    0 => 'and',
			    1 => Array
		        (
		            0 => 'and',
		            1 => Array
		            (
		                0 => 'member_field:mag-total-purchased',
		                1 => 'gt',
		                2 => 100
		            )
		        )
			)
		);

		foreach ($req_searches as $search_name => $exists)
		{
			if(!$exists)
			{
				$req = $this->add_search($data[$search_name]);

				if(!$req)
				{
					return false;
				}
			}
		}

		return true;
    }

	public function is_field_exists($key='', $value='', $fields_obj='')
	{
		if(!$key || $key=='' || !$value || $value=='' || !$fields_obj || $fields_obj=='')
		{
			return false;
		}

		$fields_obj = json_decode($fields_obj);
		
		foreach ($fields_obj as $field)
		{
			if(strtolower($field->{$key})==strtolower($value))
			{
				return $field;
			}
		}
		return false;
	}

	private function remove_default_fields($my_fields='')
	{
		$my_fields = json_decode($my_fields);

		if(!$my_fields)
		{
			return false;
		}

		$fields_to_remove = array(
        	'mag-total-orders',
        	'mag-total-purchased',
        	'mag-highest-purchase',
        	'mag-avg-purchase',
        	'mag-last-purchase'
    	);

    	$fields_to_send = array();

		foreach ($my_fields as $field)
		{
			if(!in_array($field->shortcut_name, $fields_to_remove))
			{
				$fields_to_send[] = $field;
			}
		}

		return json_encode($fields_to_send);
	}

	private function remove_default_searches($my_searches='')
	{
		$my_searches = json_decode($my_searches);

		if(!$my_searches)
		{
			return false;
		}

		$searches_to_remove = array(
			"Purchased in last year",
			"Purchased in last 30 days",
			"Have more than one total order",
			"Subscribed, but have not purchased",
			"Average purchase is greater than $50",
			"Cumulative purchases are greater than $100"
		);

    	$searches_to_send = array();

		foreach ($my_searches as $search)
		{
			if(!in_array(strtolower($search->name), $searches_to_remove))
			{
				$searches_to_send[] = $search;
			}
		}

		return json_encode($groups_to_send);
	}

	private function remove_default_groups($my_groups='')
	{
		$my_groups = json_decode($my_groups);

		if(!$my_groups)
		{
			return false;
		}

		$groups_to_remove = array('magento customers');

    	$groups_to_send = array();

		foreach ($my_groups as $group)
		{
			if(!in_array(strtolower($group->group_name), $groups_to_remove))
			{
				$groups_to_send[] = $group;
			}
		}

		return json_encode($groups_to_send);
	}

	private function _sanitize_string($string='')
	{
		if(!$string)
		{
			return $string;
		}

   		$string = str_replace(' ', '-', $string); // Replaces all spaces with hyphens.
		return preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.
	}
}