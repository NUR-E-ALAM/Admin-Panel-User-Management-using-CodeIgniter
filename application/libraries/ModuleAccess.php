<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Class : ModuleAccess
 * Base Class to control over all the classes
  * @author : Tanzir Nur
 * @version : 1.1
 * @since : 27 May 2023
 */

class ModuleAccess {

	private $CI;

    public function __construct()
    {
        $this->CI = &get_instance();
    	$this->CI->config->item('moduleList');
    }
}