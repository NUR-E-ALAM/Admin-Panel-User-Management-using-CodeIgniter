<?php if(!defined('BASEPATH')) exit('No direct script access allowed');

require APPPATH . '/libraries/BaseController.php';

/**
 * Class : User (SimpleUserController)
 * User Class to control all simple_user_model related operations.
  * @author : Tanzir Nur
 * @version : 1.1
 * @since : 29 May 2023
 */
class SimpleUser extends BaseController
{
    /**
     * This is default constructor of the class
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->model('simple_user_model');
        $this->load->library(["upload"]);
        $this->isLoggedIn();
    }
    
    /**
     * This function used to load the first screen of the simple_user_model
     */
    public function index()
    {
        $this->global['pageTitle'] = 'TanZirNur : Dashboard';
        
        $this->loadViews("general/dashboard", $this->global, NULL , NULL);
    }
    
    /**
     * This function is used to load the simple_user_model list
     */
    function userListing()
    {
          
             
           
            $searchText = '';
            if(!empty($this->input->post('searchText'))) {
                $searchText = $this->security->xss_clean($this->input->post('searchText'));
            }
            $data['searchText'] = $searchText;
            
            $this->load->library('pagination');
            
            $count = $this->simple_user_model->userListingCount($searchText);

			$returns = $this->paginationCompress ( "userListing/", $count, 10 );
            
            $data['userRecords'] = $this->simple_user_model->userListing($searchText, $returns["page"], $returns["segment"]);
            
            $this->global['pageTitle'] = 'TanZirNur : Users';
            
            $this->loadViews("simpleusers/users", $this->global, $data, NULL);
        
    }

    /**
     * This function is used to load the add new form
     */
    function addNew()
    {
       
            $this->load->model('simple_user_model');
            $data['roles'] = $this->simple_user_model->getUserRoles();
            
            $this->global['pageTitle'] = 'TanZirNur : Add New User';

            $this->loadViews("simpleusers/addNew", $this->global, $data, NULL);
        
    }

    /**
     * This function is used to check whether email already exist or not
     */
    function checkEmailExists()
    {
        $userId = $this->input->post("userId");
        $email = $this->input->post("email");

        if(empty($userId)){
            $result = $this->simple_user_model->checkEmailExists($email);
        } else {
            $result = $this->simple_user_model->checkEmailExists($email, $userId);
        }

        if(empty($result)){ echo("true"); }
        else { echo("false"); }
    }
    
    /**
     * This function is used to add new simple_user_model to the system
     */
    function addNewUser()
    {
       
            $this->load->library('form_validation');
            
            $this->form_validation->set_rules('fname','Full Name','trim|required|max_length[128]');
            $this->form_validation->set_rules('email','Email','trim|required|valid_email|max_length[128]');
            $this->form_validation->set_rules('password','Password','required|max_length[20]');
            $this->form_validation->set_rules('cpassword','Confirm Password','trim|required|matches[password]|max_length[20]');
            $this->form_validation->set_rules('mobile','Mobile Number','required|min_length[10]');
            
            if($this->form_validation->run() == FALSE)
            {
                $this->addNew();
            }
            else
            {
                $name = ucwords(strtolower($this->security->xss_clean($this->input->post('fname'))));
                $email = strtolower($this->security->xss_clean($this->input->post('email')));
                $password = $this->input->post('password');
                $roleId = 0;
                $mobile = $this->security->xss_clean($this->input->post('mobile'));
                $isAdmin = 0;
                if (!is_dir(FCPATH . "/assets/images/users/")) {
                    mkdir(FCPATH . "/assets/images/users/", 0777, true);
                }
                $config['upload_path'] = FCPATH . "/assets/images/users/";
                $config['allowed_types'] = 'gif|jpg|png|bmp|jpeg';
                $images = time() . "_" . $_FILES["images"]['name'];
                $config['file_name'] = $images;
                $this->upload->initialize($config);
                $this->load->library('upload', $config);
                if (!$this->upload->do_upload('images')) {
                   
                    $this->session->set_flashdata('error', $this->upload->display_errors());
                    $this->addNew();
                } else {
                 $data['images'] =  $images;
                }

                
                $userInfo = array(
                    'email'=>$email, 
                'password'=>getHashedPassword($password), 
                'roleId'=>$roleId, 
                'name'=> $name, 
                'mobile'=>$mobile,
                'images'=>$images,
                'status'=>0,
                'isAdmin'=>$isAdmin,
                'createdBy'=>$this->vendorId,
                'createdDtm'=>date('Y-m-d H:i:s')
            );
                
                $this->load->model('simple_user_model');
                $result = $this->simple_user_model->addNewUser($userInfo);
                
                if($result > 0){
                    $this->session->set_flashdata('success', 'New User created successfully');
                } else {
                    $this->session->set_flashdata('error', 'User creation failed');
                }
                
                redirect('users');
            }
        
    }

    
    /**
     * This function is used load simple_user_model edit information
     * @param number $userId : Optional : This is simple_user_model id
     */
    function editOld($userId = NULL)
    {
       
            if($userId == null)
            {
                redirect('users');
            }
            
            $data['roles'] = $this->simple_user_model->getUserRoles();
            $data['userInfo'] = $this->simple_user_model->getUserInfo($userId);

            $this->global['pageTitle'] = 'TanZirNur : Edit User';
            
            $this->loadViews("simpleusers/editOld", $this->global, $data, NULL);
        
    }
    
    
    /**
     * This function is used to edit the simple_user_model information
     */
    function editUser()
    {
       
            $this->load->library('form_validation');
            
            $userId = $this->input->post('userId');
            
            
            $this->form_validation->set_rules('fname','Full Name','trim|required|max_length[128]');
            $this->form_validation->set_rules('email','Email','trim|required|valid_email|max_length[128]');
            $this->form_validation->set_rules('password','Password','matches[cpassword]|max_length[20]');
            $this->form_validation->set_rules('cpassword','Confirm Password','matches[password]|max_length[20]');
            $this->form_validation->set_rules('mobile','Mobile Number','required|min_length[10]');


            $user =  $this->simple_user_model->get_user_id($userId);
            $prevImage =   FCPATH . "/assets/images/users/" . $user->images;
            if($this->form_validation->run() == FALSE)
            {
                $this->editOld($userId);
            }
            else
            {
                $name = ucwords(strtolower($this->security->xss_clean($this->input->post('fname'))));
                $email = strtolower($this->security->xss_clean($this->input->post('email')));
                $password = $this->input->post('password');
                $roleId = 0;
                $status = 0;
                $mobile = $this->security->xss_clean($this->input->post('mobile'));
                $isAdmin = 0;
                
                $userInfo = array();
                
                if(empty($password))
                {
                    $userInfo = array('email'=>$email, 'roleId'=>$roleId,'status'=>$status, 'name'=>$name, 'mobile'=>$mobile,
                        'isAdmin'=>$isAdmin, 'updatedBy'=>$this->vendorId, 'updatedDtm'=>date('Y-m-d H:i:s'));
                }
                else
                {
                    $userInfo = array('email'=>$email, 'password'=>getHashedPassword($password), 'roleId'=>$roleId,'status'=>$status,
                        'name'=>ucwords($name), 'mobile'=>$mobile, 'isAdmin'=>$isAdmin, 
                        'updatedBy'=>$this->vendorId, 'updatedDtm'=>date('Y-m-d H:i:s'));
                }
                $config['upload_path'] = FCPATH . "/assets/images/users/";
                $config['allowed_types'] = 'gif|jpg|png|bmp|jpeg';
                $new_name = time() . "_" . $_FILES["images"]['name'];
                $config['file_name'] = $new_name;
                $this->upload->initialize($config);
                $this->load->library('upload', $config);
                if (!$this->upload->do_upload('images')) {
                 
                    $this->session->set_flashdata('error',  $this->upload->display_errors());
                } else {
                    $userInfo['images'] =  $new_name;
                    // Remove old file from the server  
                    if (!empty($prevImage)) {
                        @unlink($prevImage);
                    }
                }

                
                $result = $this->simple_user_model->editUser($userInfo, $userId);
                
                if($result == true)
                {
                    $this->session->set_flashdata('success', 'User updated successfully');
                }
                else
                {
                    $this->session->set_flashdata('error', 'User updation failed');
                }
                
                redirect('users');
            }
        
    }


    /**
     * This function is used to delete the simple_user_model using userId
     * @return boolean $result : TRUE / FALSE
     */
    function deleteSimpleUser()
    {
      
            $userId = $this->input->post('userId');
            // var_dump($userId);exit;
            $userInfo = array('isDeleted'=>1,'updatedBy'=>$this->vendorId, 'updatedDtm'=>date('Y-m-d H:i:s'));
            
            $result = $this->simple_user_model->deleteUser($userId, $userInfo);
            
            if ($result > 0) { echo(json_encode(array('status'=>TRUE))); }
            else { echo(json_encode(array('status'=>FALSE))); }
        
    }

    /**
     * This function is used to status update the simple_user_model using userId
     * @return boolean $result : TRUE / FALSE
     */
    function statusUpdate()
    {
        if(!$this->isAdmin())
        {
            echo(json_encode(array('status'=>'access')));
        }
        else
        {
            $userId = $this->input->post('userId');
            $user =  $this->simple_user_model->get_user_id($userId);
            if($user->status == 1){
                $userInfo = array('status'=>0,'updatedBy'=>$this->vendorId, 'updatedDtm'=>date('Y-m-d H:i:s'));
            }
            else{
                $userInfo = array('status'=>1,'updatedBy'=>$this->vendorId, 'updatedDtm'=>date('Y-m-d H:i:s'));
            }
           
            
            $result = $this->simple_user_model->updateUserStatus($userId, $userInfo);
            if ($result > 0) { echo(json_encode(array('status'=>TRUE))); }
            else { echo(json_encode(array('status'=>FALSE))); }
        }
    }
    
    /**
     * Page not found : error 404
     */
    function pageNotFound()
    {
        $this->global['pageTitle'] = 'TanZirNur : 404 - Page Not Found';
        
        $this->loadViews("general/404", $this->global, NULL, NULL);
    }

    /**
     * This function used to show login history
     * @param number $userId : This is simple_user_model id
     */
    function loginHistoy($userId = NULL)
    {
        if(!$this->isAdmin())
        {
            $this->loadThis();
        }
        else
        {
            $userId = ($userId == NULL ? 0 : $userId);

            $searchText = $this->input->post('searchText');
            $fromDate = $this->input->post('fromDate');
            $toDate = $this->input->post('toDate');

            $data["userInfo"] = $this->simple_user_model->getUserInfoById($userId);

            $data['searchText'] = $searchText;
            $data['fromDate'] = $fromDate;
            $data['toDate'] = $toDate;
            
            $this->load->library('pagination');
            
            $count = $this->simple_user_model->loginHistoryCount($userId, $searchText, $fromDate, $toDate);

            $returns = $this->paginationCompress ( "login-history/".$userId."/", $count, 10, 3);

            $data['userRecords'] = $this->simple_user_model->loginHistory($userId, $searchText, $fromDate, $toDate, $returns["page"], $returns["segment"]);
            
            $this->global['pageTitle'] = 'TanZirNur : User Login History';
            
            $this->loadViews("simpleusers/loginHistory", $this->global, $data, NULL);
        }        
    }

    /**
     * This function is used to show users profile
     */
    function profile($active = "details")
    {
        $data["userInfo"] = $this->simple_user_model->getUserInfoWithRole($this->vendorId);
        $data["active"] = $active;
        
        $this->global['pageTitle'] = $active == "details" ? 'TanZirNur : My Profile' : 'TanZirNur : Change Password';
        $this->loadViews("simpleusers/profile", $this->global, $data, NULL);
    }

    /**
     * This function is used to update the simple_user_model details
     * @param text $active : This is flag to set the active tab
     */
    function profileUpdate($active = "details")
    {
        $this->load->library('form_validation');
            
        $this->form_validation->set_rules('fname','Full Name','trim|required|max_length[128]');
        $this->form_validation->set_rules('mobile','Mobile Number','required|min_length[10]');
        $this->form_validation->set_rules('email','Email','trim|required|valid_email|max_length[128]|callback_emailExists');        
        
        if($this->form_validation->run() == FALSE)
        {
            $this->profile($active);
        }
        else
        {
            $name = ucwords(strtolower($this->security->xss_clean($this->input->post('fname'))));
            $mobile = $this->security->xss_clean($this->input->post('mobile'));
            $email = strtolower($this->security->xss_clean($this->input->post('email')));
            
            $userInfo = array('name'=>$name, 'email'=>$email, 'mobile'=>$mobile, 'updatedBy'=>$this->vendorId, 'updatedDtm'=>date('Y-m-d H:i:s'));
            
            $result = $this->simple_user_model->editUser($userInfo, $this->vendorId);
            
            if($result == true)
            {
                $this->session->set_userdata('name', $name);
                $this->session->set_flashdata('success', 'Profile updated successfully');
            }
            else
            {
                $this->session->set_flashdata('error', 'Profile updation failed');
            }

            redirect('profile/'.$active);
        }
    }

    /**
     * This function is used to change the password of the simple_user_model
     * @param text $active : This is flag to set the active tab
     */
    function changePassword($active = "changepass")
    {
        $this->load->library('form_validation');
        
        $this->form_validation->set_rules('oldPassword','Old password','required|max_length[20]');
        $this->form_validation->set_rules('newPassword','New password','required|max_length[20]');
        $this->form_validation->set_rules('cNewPassword','Confirm new password','required|matches[newPassword]|max_length[20]');
        
        if($this->form_validation->run() == FALSE)
        {
            $this->profile($active);
        }
        else
        {
            $oldPassword = $this->input->post('oldPassword');
            $newPassword = $this->input->post('newPassword');
            
            $resultPas = $this->simple_user_model->matchOldPassword($this->vendorId, $oldPassword);
            
            if(empty($resultPas))
            {
                $this->session->set_flashdata('nomatch', 'Your old password is not correct');
                redirect('profile/'.$active);
            }
            else
            {
                $usersData = array('password'=>getHashedPassword($newPassword), 'updatedBy'=>$this->vendorId,
                                'updatedDtm'=>date('Y-m-d H:i:s'));
                
                $result = $this->simple_user_model->changePassword($this->vendorId, $usersData);
                
                if($result > 0) { $this->session->set_flashdata('success', 'Password updation successful'); }
                else { $this->session->set_flashdata('error', 'Password updation failed'); }
                
                redirect('profile/'.$active);
            }
        }
    }

    /**
     * This function is used to check whether email already exist or not
     * @param {string} $email : This is users email
     */
    function emailExists($email)
    {
        $userId = $this->vendorId;
        $return = false;

        if(empty($userId)){
            $result = $this->simple_user_model->checkEmailExists($email);
        } else {
            $result = $this->simple_user_model->checkEmailExists($email, $userId);
        }

        if(empty($result)){ $return = true; }
        else {
            $this->form_validation->set_message('emailExists', 'The {field} already taken');
            $return = false;
        }

        return $return;
    }
}

?>