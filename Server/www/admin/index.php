<?php
/*
 * UNS Admin page, this page is used to manage the UNS Install.
 * Create, modify, and delete clients, users, custom messages, 
 * RSS feeds, and emergency messages and state.
 * 
 * @author Phillip Ferland <pferland@randomintervals.com>
 * @link http://uns.randomintervals.com UNS Site
 * @date 6/10/2012
 * @version 1.0
 * 
 * Copyright (C) 2012  Phillip Ferland
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see http://www.gnu.org/licenses/
 */

/* Include and create the UNS Object*/
require '../lib/UNSAdmin.inc.php';
$UNSAdmin = new UNSAdmin();
$UNSAdmin->parse_edit_url($_REQUEST);

if($UNSAdmin->parsed_edit_uri['func'] == 'password_reset')
{
    if(@$UNSAdmin->parsed_edit_uri['password_reset_flag'])
    {
        if($UNSAdmin->SetPassword())
        {$UNSAdmin->Redirect("");}
        #else
        #{$UNSAdmin->Redirect("?func=password_reset&hash={$UNSAdmin->reset_hash}");}
        
        $UNSAdmin->ShowResults("Password Reset Results.");
    }else
    {
        if($UNSAdmin->PrepPasswordReset())
        {
            #$UNSAdmin->Redirect("?func=password_reset&hash={$UNSAdmin->reset_hash}");
            $UNSAdmin->smarty->assign("hash", $UNSAdmin->reset_hash);
            $UNSAdmin->smarty->assign("username", $UNSAdmin->reset_username);
            $UNSAdmin->smarty->display("reset_password.tpl");
        }
    }
    exit;
}

/* Filter and set variables*/
$login_flag = @filter_input(INPUT_GET, 'login', FILTER_SANITIZE_NUMBER_INT);
$update_pwd_flag = filter_input(INPUT_POST, "update_password", FILTER_SANITIZE_NUMBER_INT);
$update_pwd_flag_get = filter_input(INPUT_GET, "update_password", FILTER_SANITIZE_NUMBER_INT);
$UNSAdmin->update_pwd = $update_pwd_flag_get;

/* Update the password if it was needed*/
if($update_pwd_flag && $update_pwd_flag_get)
{
    if($UNSAdmin->UpdatePassword())
    {
        $UNSAdmin->DisplayPage("admin_login.tpl", "Password Update was Successful");
    }
}

/* Check the users login, or ask them to*/
if($login_flag)
{
    $UNSAdmin->username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $UNSAdmin->password_unen = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_STRING);
    switch($UNSAdmin->login())
    {
        case -2: /* Passwords do not match, bad login, increment failed login flag, Log it, and tell the user.*/
            if($UNSAdmin->logins_left === 1){$try = "try";}else{$try = "tries";}
            $UNSAdmin->smarty->assign("message", "Your password did not match, please try again. You have {$UNSAdmin->logins_left} more {$try}.");
            $UNSAdmin->Log("{$UNSAdmin->username} has attemted to login, but the password was wrong.");
            $UNSAdmin->smarty->display("admin_login.tpl");
            exit();
            break;
        
        case -1; /* A disabled user tried to login, this isnt good. Log it and tell the user.*/
            $UNSAdmin->smarty->assign("message", "User has been disabled. Contact your Administrator.");
            $UNSAdmin->Log("{$UNSAdmin->username} has attemted to login, but the account is locked.");
            $UNSAdmin->smarty->display("admin_login.tpl");
            exit();
            break;
        
        case 0: /* Umm... what? */
            echo "Ummmm....What?";
            break;
        
        case 1: /* User has successfully logged into UNS */
            $UNSAdmin->Log("{$UNSAdmin->username} has succesfully logged in.");
            $UNSAdmin->Redirect("");
            
            #$UNSAdmin->smarty->assign("message", "Login Successful!");
            #$UNSAdmin->smarty->display("admin_login.tpl");
            break;
    }
}
/* Main Switcher for the Admin Page */
if($UNSAdmin->LoginCheck())
{
    $UNSAdmin->GenTzOpts();
    $UNSAdmin->GetPermissions();
    if(!$UNSAdmin->CheckPermissions())
    {
        $UNSAdmin->AddMessage("You are not provisioned to use the area that you have selected, please go back and stay within the playground.", 1);
        $UNSAdmin->ShowResults();
    }
    $UNSAdmin->client = @filter_input(INPUT_GET, 'client', FILTER_SANITIZE_STRING);
    $UNSAdmin->smarty->assign("client", $UNSAdmin->client);
    $UNSAdmin->smarty->assign("host_path", $UNSAdmin->host_path);
    
    if($UNSAdmin->led_blink)
    {
        $blink_flag = "";
    }else
    {
        $blink_flag = "disabled";
    }
    $UNSAdmin->smarty->assign("led_blink", $blink_flag);
    switch($UNSAdmin->parsed_edit_uri['func'])
    {
        /* Change the timezone */
        case "chg_tz":
            $cl_timezone = $UNSAdmin->parsed_edit_uri['cl_timezone'];
            $sql = "UPDATE `{$UNSAdmin->sql->db}`.`allowed_users` SET `tz` = '$cl_timezone' WHERE `username` = '{$UNSAdmin->username}'";
            if($UNSAdmin->sql->conn->query($sql))
            {
                echo "Changed Time Zone.";
                $UNSAdmin->Redirect($UNSAdmin->admin_path);
            }else
            {
                echo "Failed to Change Time Zone.<br />\r\n".$UNSAdmin->sql->conn->error;
            }
            break;
        
        case "logout":
            switch($UNSAdmin->Logout())
            {
                case 1:
                    echo "Logout Successful!";
                    $UNSAdmin->Redirect("");
                    break;
            
                case -1:
                    $UNSAdmin->smarty->assign("message", "User has been disabled. Contact your Administrator.");
                    $UNSAdmin->Log("Error Removing login hash from table for {$UNSAdmin->username}");
                    $UNSAdmin->smarty->display("admin_login.tpl");
                    break;
                
                case 0;
                    echo "Ummm.... You didnt even have a cookie....";
                    break;
            }
            
            break;
        
        case "view_client":
            $UNSAdmin->GetClient();
            $UNSAdmin->smarty->display("client_view.tpl");
            break;
        
        case "edit_urls": 
            /* Things that you can do to the URLS of clients */
            $UNSAdmin->GetFriendly();
            $UNSAdmin->smarty->assign("cl_func", $UNSAdmin->parsed_edit_uri['cl_func']);
            switch($UNSAdmin->parsed_edit_uri['cl_func'])
            {
                case "copy2_proc":
                    $UNSAdmin->ids_imp = filter_input(INPUT_POST, "allids", FILTER_SANITIZE_STRING);
                    $UNSAdmin->CopyClientURLS();
                    $UNSAdmin->Redirect("?func=view_client&client={$UNSAdmin->client}");
                    $UNSAdmin->ShowResults("Copy Client Links");
                    break;
                
                case "save_new": /* Save the selected URLs of a client to a list */
                    $UNSAdmin->SaveList();
                    $UNSAdmin->Redirect("?func=view_client&client={$UNSAdmin->client}");
                    $UNSAdmin->ShowResults("Save URL List");
                    break;
                
                case "add_url_batch":
                    $UNSAdmin->AddURLs();
                    $UNSAdmin->Redirect("?func=view_client&client={$UNSAdmin->client}");
                    $UNSAdmin->ShowResults("Add New URLs to Client: {$UNSAdmin->client}");
                    break;
                
                case "edit_proc":
                    $UNSAdmin->smarty->assign("jobtodo", $UNSAdmin->parsed_edit_uri['jobtodo']);
                    switch(strtolower($UNSAdmin->parsed_edit_uri['jobtodo']))
                    {
                        case "copy":
                            $UNSAdmin->GetClientList($UNSAdmin->client);
                            $UNSAdmin->smarty->assign("clients", $UNSAdmin->client_list);
                            $UNSAdmin->smarty->assign("allids", implode("|",$_POST['ids']));
                            $UNSAdmin->smarty->display("copy_links_client_select.tpl");
                            break;
                        
                        case "save to list":
                            $UNSAdmin->GetSavedLists();
                            $UNSAdmin->smarty->assign("saved_lists", $UNSAdmin->SavedLists);
                            $UNSAdmin->smarty->assign("urls_imp", implode("|",$_POST['urls']));
                            $UNSAdmin->smarty->display("save_list_details.tpl");
                            break;
                        
                        case "remove":
                            switch(strtolower(@$UNSAdmin->parsed_edit_uri['save_q']))
                            {
                                case "yes":
                                    $UNSAdmin->RemoveURLs($UNSAdmin->parsed_edit_uri['allids']);
                                    $UNSAdmin->Redirect("?func=view_client&client={$UNSAdmin->client}");
                                    $UNSAdmin->ShowResults("Removed URL");
                                    break;
                                case "no":
                                    $UNSAdmin->Redirect("?func=view_client&client={$UNSAdmin->client}");
                                    $UNSAdmin->AddMessage("User choose not to remove the URL(s)");
                                    $UNSAdmin->ShowResults("");
                                    break;
                                default:
                                    $ids = array();
                                    foreach($_REQUEST['ids'] as $id)
                                    {
                                        $ids[] = filter_var($id, FILTER_SANITIZE_NUMBER_INT);
                                    }
                                    $UNSAdmin->smarty->assign("allids", implode("|", $ids));
                                    $UNSAdmin->smarty->assign("Question", "Are you sure you want to remove this/these URL(s)? <br>[".implode("|", $ids)."] </br>
                                        If you do it will be archived.");
                                    $UNSAdmin->DisplayPage('ask_user.tpl');
                                    break;
                            }
                            break;
                        
                        case "disable":
                            $UNSAdmin->DisableURLs();
                            $UNSAdmin->Redirect("?func=view_client&client={$UNSAdmin->client}");
                            $UNSAdmin->ShowResults("Toggle Disable/Enable for URLs on Client: {$UNSAdmin->client}");
                            break;
                        
                        case "set all":
                            $UNSAdmin->SetRefreshURLS();
                            $UNSAdmin->Redirect("?func=view_client&client={$UNSAdmin->client}");
                            $UNSAdmin->ShowResults("Updated Refresh time for URLs on Client: {$UNSAdmin->client}");
                            break;
                        
                        default:
                            exit("go away.");
                            break;
                    }
                    
                    break;
                
                case "restore_saved":
                    $saved_id = $UNSAdmin->parsed_edit_uri['id'];
                    switch(strtolower(@$UNSAdmin->parsed_edit_uri['save_q']))
                    {
                        case "yes":
                            $UNSAdmin->RestoreSavedList($saved_id);
                            $UNSAdmin->Redirect("?func=view_client&client={$UNSAdmin->client}");
                            $UNSAdmin->ShowResults("Restore Saved URL List");
                            break;
                        case "no":
                            $UNSAdmin->Redirect("?func=view_client&client={$UNSAdmin->client}");
                            $UNSAdmin->AddMessage("User choose not to restore URLS");
                            $UNSAdmin->ShowResults("Restore Saved URLs List");
                            break;
                        default:
                            $UNSAdmin->smarty->assign("Question", "Are you sure you want to restore this Saved URL List? </br>
                                If you do this it will remove the current ones and replace them with the saved list. </br>
                                Dont worry though, the URLS that were there will be archived.");
                            $UNSAdmin->DisplayPage('ask_user.tpl');
                            break;
                    }
                    break;
                
                case "remove_saved":
                    $saved_id = $UNSAdmin->parsed_edit_uri['id'];
                    switch(strtolower(@$UNSAdmin->parsed_edit_uri['save_q']))
                    {
                        case "yes":
                            $UNSAdmin->RemoveSavedList($saved_id);
                            $UNSAdmin->Redirect("?func=view_client&client={$UNSAdmin->client}");
                            $UNSAdmin->ShowResults("Removed Saved URL List");
                            break;
                        case "no":
                            $UNSAdmin->Redirect("?func=view_client&client={$UNSAdmin->client}");
                            $UNSAdmin->AddMessage("User choose not to restore URLS");
                            $UNSAdmin->ShowResults("Removal of Saved URLs List");
                            break;
                        default:
                            $UNSAdmin->smarty->assign("saved_id", $saved_id);
                            $UNSAdmin->smarty->assign("Question", "Are you sure you want to remove this/these Saved URL List(s)? </br>
                                If you do, you will not have this list any more.");
                            $UNSAdmin->DisplayPage('ask_user.tpl');
                            break;
                    }
                    break;
                
                case "remove_archive":
                    switch(strtolower(@$UNSAdmin->parsed_edit_uri['save_q']))
                    {
                         case "yes":
                            $allids = $UNSAdmin->parsed_edit_uri['allids'];
                            $UNSAdmin->RemoveArchivedList($allids);
                            $UNSAdmin->Redirect("?func=view_client&client={$UNSAdmin->client}");
                            $UNSAdmin->ShowResults("Removed Archived URL List");
                            break;
                        case "no":
                            $UNSAdmin->Redirect("?func=view_client&client={$UNSAdmin->client}");
                            $UNSAdmin->AddMessage("User choose NOT to restore URLS");
                            $UNSAdmin->ShowResults("Removal of Archived List.");
                            break;
                        default:
                            $allids = implode("|", $_REQUEST['ids']);
                            $UNSAdmin->smarty->assign("allids", $allids);
                            $UNSAdmin->smarty->assign("Question", "Are you sure you want to remove this/these Archived List(s)? </br>
                                If you do this you will no longer have this list.</br>
                                {$allids}");
                            $UNSAdmin->DisplayPage('ask_user.tpl');
                            break;
                    }
                    break;
                
                case "restore_archive":
                    $saved_id = $UNSAdmin->parsed_edit_uri['id'];
                    switch(strtolower(@$UNSAdmin->parsed_edit_uri['save_q']))
                    {
                         case "yes":
                            $saved_id = $UNSAdmin->parsed_edit_uri['id'];
                            $UNSAdmin->RestoreArcivedList($saved_id);
                            $UNSAdmin->Redirect("?func=view_client&client={$UNSAdmin->client}");
                            $UNSAdmin->ShowResults("Restored Archived URL List");
                            break;
                        case "no":
                            $UNSAdmin->Redirect("?func=view_client&client={$UNSAdmin->client}");
                            $UNSAdmin->AddMessage("User choose not to restore Archived URL List");
                            $UNSAdmin->ShowResults("Restore URLS");
                            break;
                        default:
                            $UNSAdmin->smarty->assign("saved_id", $saved_id);
                            $UNSAdmin->smarty->assign("Question", "Are you sure you want to restore this Archived URL List? </br>
                                If you do this it will remove the current ones and replace them with the saved list. </br>
                                Dont worry though, the URLS that were there will be archived.");
                            $UNSAdmin->DisplayPage('ask_user.tpl');
                            break;
                    }
                    break;
                
                case "save_append":
                    $append_id = $UNSAdmin->parsed_edit_uri['saved'];
                    $UNSAdmin->SaveList($append_id);
                    $UNSAdmin->Redirect("?func=view_client&client={$UNSAdmin->client}");
                    $UNSAdmin->ShowResults("Append Saved URL List");
                    
                    break;
                
            }
            break;
        
        case "add_client":
            $name = $UNSAdmin->parsed_edit_uri['friendly'];
            if(!$UNSAdmin->CreateClient($name, 1))
            {
                throw new Exception("Failed to create new client.");
            }
            $UNSAdmin->Redirect('');
            $UNSAdmin->ShowResults("Add Client");
            break;
        
        case "remove_cl":
            if(!$UNSAdmin->RemoveClients())
            {
                throw new Exception("Failed to remove client.");
            }
            $UNSAdmin->Redirect('');
            $UNSAdmin->ShowResults("Remove Client");
            break;
            
        case "rename_client":
            $UNSAdmin->GetFriendly();
            $UNSAdmin->client = $UNSAdmin->parsed_edit_uri['client_id'];
            $UNSAdmin->clientNewName = $UNSAdmin->parsed_edit_uri['client_name'];
            
            $UNSAdmin->RenameClient();
            
            $UNSAdmin->Redirect("?func=view_client&client={$UNSAdmin->client}");
            $UNSAdmin->ShowResults("Renamed Client ({$UNSAdmin->friendly}) to ({$UNSAdmin->clientNewName})");
            break;
        
        case "client_led_set":
            $UNSAdmin->GetFriendly();
            $UNSAdmin->ChangeClientLED($UNSAdmin->parsed_edit_uri['cl_led_id']);
            
            $UNSAdmin->Redirect("?func=view_client&client={$UNSAdmin->client}");
            $UNSAdmin->ShowResults("Set Client LED Group");
            break;
        
        case "img_messages":
            $UNSAdmin->GetImgMessages();
            $UNSAdmin->smarty->assign("AllImgMessages", $UNSAdmin->AllImgMessages);
            $UNSAdmin->smarty->display("ImgMessages.tpl");
            
            break;
        
        case "edit_emerg":
            $UNSAdmin->GetEmergFlag();
            if($UNSAdmin->emerg_flag)
            {
                $toggle_label = "Disable";
            }else
            {
                $toggle_label = "Enable";
            }
            $UNSAdmin->GetEmergencyMessages();
            $UNSAdmin->smarty->assign("all_emerg_msgs", $UNSAdmin->all_emerg_msgs);
            $UNSAdmin->smarty->assign("Toggle_Label", $toggle_label);
            $UNSAdmin->smarty->display("emergency.tpl");
            break;
        
        case "emerg_proc":
            switch(strtolower(@$UNSAdmin->parsed_edit_uri['jobtodo']))
            {
                case "enable/disable":
                    $UNSAdmin->ToggleEmergURL();
                    $UNSAdmin->Redirect("?func=edit_emerg");
                    $UNSAdmin->ShowResults("Toggle Emergency Message State");
                    break;
                case "delete":
                    $UNSAdmin->DeleteEmergencyMessage();
                    $UNSAdmin->Redirect("?func=edit_emerg");
                    $UNSAdmin->ShowResults("Delete Emergency Message");
                    break;
                case "update refresh":
                    $UNSAdmin->SetEmergRefreshURLS();
                    $UNSAdmin->Redirect("?func=edit_emerg");
                    $UNSAdmin->ShowResults("Set Emergency Refresh Times");
                    break;
                default:
                    #var_dump($UNSAdmin->parsed_edit_uri);
                    if(@$UNSAdmin->parsed_edit_uri['toggle_global_emerg'])
                    {
                        $UNSAdmin->ToggleEmergState();
                        $UNSAdmin->GetEmergFlag();
                        $UNSAdmin->smarty->assign("emerg_flag", $this->emerg_flag);
                        $UNSAdmin->Redirect("?func=edit_emerg");
                        $UNSAdmin->ShowResults("Toggle the Global Emergency Messages");
                    }else
                    {
                        echo "ummmmm...";
                    }
                    break;
            }
            
            break;
        
        case "add_emerg":
            $UNSAdmin->AddEmergURLs($UNSAdmin->parsed_edit_uri['new_emerg_urls']);
            $UNSAdmin->Redirect("?func=edit_emerg");
            $UNSAdmin->ShowResults("Toggle the Global Emergency Messages");
            break;
        
        case "create_user":
            $UNSAdmin->CreateUser();
            $UNSAdmin->Redirect("?func=view_users", 5);
            $UNSAdmin->ShowResults("Add New UNS User"); 
            break;
        
        case "view_users":
            $UNSAdmin->GetUserInfo($UNSAdmin->username);
            $UNSAdmin->smarty->assign("UNS_all_users", $UNSAdmin->all_user_info);
            $UNSAdmin->smarty->display("view_users.tpl");
            break;
        
        case "edit_users":
            #var_dump($UNSAdmin->parsed_edit_uri['jobtodo']);
            switch(strtolower($UNSAdmin->parsed_edit_uri['jobtodo']))
            {
                case "update permissions":
                    $UNSAdmin->UpdatePermissions();
                    $UNSAdmin->Redirect("?func=view_users");
                    $UNSAdmin->ShowResults("Update Permissions for ({$UNSAdmin->parsed_edit_uri['username']})");
                    break;
                case "remove":
                    $UNSAdmin->RemoveUsers();
                    $UNSAdmin->Redirect("?func=view_users", 5);
                    $UNSAdmin->ShowResults("Remove User: ({$UNSAdmin->parsed_edit_uri['username']})");
                    break;
                case "enable":
                    $UNSAdmin->ToggleUser();
                    $UNSAdmin->Redirect("?func=view_users");
                    $UNSAdmin->ShowResults("Disabled User");
                    break;
                case "disable":
                    $UNSAdmin->ToggleUser();
                    $UNSAdmin->Redirect("?func=view_users");
                    $UNSAdmin->ShowResults("Enabled User");
                    break;
                case "send password reset":
                    $UNSAdmin->SendPasswordReset();
                    $UNSAdmin->Redirect("?func=view_users");
                    $UNSAdmin->ShowResults("Sent User ({$UNSAdmin->parsed_edit_uri['username']}) a Password Reset Email.");
                    break;
                case "reset failed logins":
                    $UNSAdmin->ResetFailedLogin();
                    $UNSAdmin->Redirect("?func=view_users");
                    $UNSAdmin->ShowResults("Reset Failed Logins for User: {$UNSAdmin->parsed_edit_uri['username']}.");
                    break;
                default:
                    echo "ummmm....";
                    break;
            }
            break;
        
        case "view_clients":
            $UNSAdmin->GetAllClients();
            $UNSAdmin->smarty->assign("AllClients", $UNSAdmin->client_all_data);
            $UNSAdmin->smarty->display("admin_index.tpl");
            break;
        
        default:
            $UNSAdmin->Redirect("?func=view_clients", 0);
            $UNSAdmin->ShowResults("Redirect to Index");
            break;
    }
}else
{
    $UNSAdmin->smarty->display("admin_login.tpl");
}
?>