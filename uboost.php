<?php

/**
 * A library to interface with uBoost.com using cURL.
 * Currently, only basic functionality is implemented.
 *
 * Released under FREEBSD license
 *
 * @version 0.2
 * @copyright eSchool Consultants 2010
 * @author John Colvin <john.colvin@eschoolconsultants.com>
 *
 */

class uboost
{
    protected $curl;  // Holds the information about where to curl as well as the data that will be sent
    protected $base_url, $username, $password;

    public function __construct($uboost_url, $uboost_username, $uboost_password)
    {
        // uBoost account info
        $this->base_url = $uboost_url;
        $this->username = $uboost_username;
        $this->password = $uboost_password;

        $this->curl = new stdClass();
        $this->curl->data = array();  // The data that will be sent with the curl
    }


    /**
     * Adds a student to uBoost. Student data accepted as associative array $params.
     * Returns curl response xml, false otherwise.
     * Returns a Simple XML element of the cURL response XML upon success, false otherwise.
     *
     * @param array $params
     * @return mixed
     * @author John Colvin <john.colvin@eschoolconsultants.com>
     */
    function add_student($params=array())
    {

        // Username is required to create a student account
        if (!isset($params['user_name'])) {
            return false;
        }

        foreach ($params as $key => $val) {
            $this->set_account_info($key, $val);
        }

        $this->curl->url = 'accounts.xml';
        return $this->post_to_uboost();

    }

    /**
     * Posts information to uBoost. This information needs to already be set using the set_account_info or set_points_info methods.
     * Returns a Simple XML element of the cURL response XML upon success, false otherwise.
     *
     * @return mixed
     * @author John Colvin <john.colvin@eschoolconsultants.com>
     */
    protected function post_to_uboost()
    {
        return contact_uboost('POST');
    }

    protected function get_from_uboost()
    {
        return contact_uboost();
    }

    protected function contact_uboost($method='GET')
    {
        $this->curl->url = $this->base_url . $this->curl->url;
        $ch = curl_init($this->curl->url);

        if ($method == 'POST') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $this->curl->data);
        }

        curl_setopt($ch, CURLOPT_USERPWD, $this->username . ':' . $this->password);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $result = curl_exec($ch);
        $curl_info = curl_getinfo($ch);
        curl_close($ch);

        // The only valid response code for creating information is 201
        // The only valid response code for getting information is 200
        if (($method == 'POST' && $curl_info['http_code'] == '201') ||
            ($method == 'GET'  && $curl_info['http_code'] == '200')) {
            return new SimpleXMLElement($result);
        }
        return false;
    }

    /**
     * Sets a key value pair in the post data of the curl object
     *
     * @param $key
     * @param $val
     * @param $namespace The parent XML elements to be prepended as a path to this value
     * @author John Colvin <john.colvin@eschoolconsultants.com>
     */
    protected function set_post_data($key, $val, $namespace='')
    {
        if (!empty($namespace)) {
            $key = $namespace . '[' . $key . ']';
        }
        $this->curl->data[$key] = $val;
    }

    /**
     * Sets a key value data pair for uBoost account information
     *
     * @param $key
     * @param $val
     * @author John Colvin <john.colvin@eschoolconsultants.com>
     */
    protected function set_account_info($key, $val)
    {
        $this->set_post_data($key, $val, 'account');
    }

    /**
     * Sets a key value data pair for uBoost points information
     *
     * @param $key
     * @param $val
     * @author John Colvin <john.colvin@eschoolconsultants.com>
     */
    protected function set_points_info($key, $val)
    {
        $this->set_post_data($key, $val, 'points_transaction');
    }

    /**
     * Adds points to a uBoost account.
     *
     * @param integer $uboost_id The student's id number in the uboost system
     * @param integer $amount Amount of the point transaction
     * @param string $message A message about this trnascation.
     * @param string $type String that is either 'Direct Deposit' or 'Administrative'
     * @return boolean
     * @author John Colvin <john.colvin@eschoolconsultants.com>
     */
    public function add_points($uboost_id, $amount, $message='', $type='Direct Deposit')
    {
        if(!is_numeric($amount)) {
            return false;
        }

        if (!in_array($type, array('Direct Deposit', 'Administrative'))) {
            return false;
        }

        $now = date('Y-m-d') . 'T' . date('H:i:sP');
        $this->set_points_info('account_id', $uboost_id);
        $this->set_points_info('points_change', $amount);
        $this->set_points_info('transaction_description', $message);
        $this->set_points_info('transaction_type', $type);
        $this->set_points_info('transaction_time', $now);

        $this->curl->url = 'points_transactions.xml';
        if ($this->post_to_uboost()) {
            return true;
        }
        return false;
    }

    /**
     * Returns a simple XML element object with the student ID, sso-token and the expiration date of the token
     * Returns false on failure
     *
     * @param integer $uboost_id The student's id number in the uboost system
     * @author John Colvin
     */
    public function get_sso($uboost_id)
    {
        $this->curl->url = 'accounts/' . $uboost_id . '/sign_in_user.xml';
        return $this->get_from_uboost();
    }

}
