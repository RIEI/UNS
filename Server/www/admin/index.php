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


/* Filter and set variables*/
$login_flag = @filter_input(INPUT_GET, 'login', FILTER_SANITIZE_NUMBER_INT)+0;
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
    $UNSAdmin->password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_STRING);
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
    $UNSAdmin->client = @filter_input(INPUT_GET, 'client', FILTER_SANITIZE_STRING);
    $func = @filter_input(INPUT_GET, 'func', FILTER_SANITIZE_STRING);
    switch($func)
    {
        case "chg_tz": /* Change the timezone */
            $cl_timezone = filter_input(INPUT_POST, 'cl_timezone', FILTER_SANITIZE_SPECIAL_CHARS);
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
                    #echo "Logout Successful!";
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
            $UNSAdmin->parse_edit_url($_REQUEST);
            $UNSAdmin->smarty->assign("cl_func", $UNSAdmin->parsed_edit_uri['cl_func']);
            switch($UNSAdmin->parsed_edit_uri['cl_func'])
            {
                case "copy2_proc":
                    $UNSAdmin->url_imp = filter_input(INPUT_POST, "urls", FILTER_SANITIZE_STRING);
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
                    switch($UNSAdmin->parsed_edit_uri['jobtodo'])
                    {
                        case "Copy":
                            $UNSAdmin->GetClientList($UNSAdmin->client);
                            
                            $UNSAdmin->smarty->assign("clients", $UNSAdmin->client_list);
                            $UNSAdmin->smarty->assign("urls_imp", implode("|",$_POST['urls']));
                            $UNSAdmin->smarty->assign("client", $UNSAdmin->client);
                            
                            $UNSAdmin->smarty->display("copy_links_client_select.tpl");
                            
                            break;
                        
                        case "Save To List":
                            $UNSAdmin->GetSavedLists();
                            $UNSAdmin->smarty->assign("saved_lists", $UNSAdmin->SavedLists);
                            $UNSAdmin->smarty->assign("urls_imp", implode("|",$_POST['urls']));
                            $UNSAdmin->smarty->assign("client", $UNSAdmin->client);
                            $UNSAdmin->smarty->display("save_list_details.tpl");
                            break;
                        
                        case "Remove":
                            
                            break;
                        
                        case "Disable":
                            
                            break;
                        
                        case "Set All":
                            $UNSAdmin->SetRefreshURLS();
                            $UNSAdmin->Redirect("?func=view_client&client={$UNSAdmin->client}");
                            $UNSAdmin->ShowResults("Add New URLs to Client: {$UNSAdmin->client}");
                            
                            break;
                        default:
                            exit("go away.");
                            break;
                    }
                    
                    break;
                
                case "restore_saved":
                    
                    switch(strtolower($UNSAdmin->parse_edit_url['yes_save']))
                    {
                        case "yes":
                            $saved_id = $UNSAdmin->parsed_edit_uri['id'];
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
                            $UNSAdmin->smarty->dispaly('ask_user.tpl');
                            break;
                    }
                    break;
                
                case "remove_saved":
                    switch(strtolower($UNSAdmin->parse_edit_url['yes_save']))
                    {
                        case "yes":
                            $saved_id = $UNSAdmin->parsed_edit_uri['id'];
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
                            $UNSAdmin->smarty->assign("Question", "Are you sure you want to remove these Saved URL List? </br>
                                If you do, you will not have this list any more.");
                            $UNSAdmin->smarty->dispaly('ask_user.tpl');
                            break;
                    }
                    break;
                
                case "removed_archive":
                    switch(strtolower($UNSAdmin->parse_edit_url['yes_save']))
                    {
                         case "yes":
                            $saved_id = $UNSAdmin->parsed_edit_uri['id'];
                            $UNSAdmin->RemoveArchivedList($saved_id);
                            $UNSAdmin->Redirect("?func=view_client&client={$UNSAdmin->client}");
                            $UNSAdmin->ShowResults("Removed Archived URL List");
                            break;
                        case "no":
                            $UNSAdmin->Redirect("?func=view_client&client={$UNSAdmin->client}");
                            $UNSAdmin->AddMessage("User choose NOT to restore URLS");
                            $UNSAdmin->ShowResults("Removal of Archived List.");
                            break;
                        default:
                            $UNSAdmin->smarty->assign("Question", "Are you sure you want to remove this Archived URL List? </br>
                                If you do this you will no longer have this list.");
                            $UNSAdmin->smarty->dispaly('ask_user.tpl');
                            break;
                    }
                    break;
                
                case "restore_archive":
                    switch(strtolower($UNSAdmin->parse_edit_url['yes_save']))
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
                            $UNSAdmin->smarty->assign("Question", "Are you sure you want to restore this Archived URL List? </br>
                                If you do this it will remove the current ones and replace them with the saved list. </br>
                                Dont worry though, the URLS that were there will be archived.");
                            $UNSAdmin->smarty->dispaly('ask_user.tpl');
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
            $name = filter_input(INPUT_POST, 'friendly', FILTER_SANITIZE_STRING);
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
            $UNSAdmin->clientNewName = filter_input(INPUT_POST, 'client_name', FILTER_SANITIZE_STRING);
            $UNSAdmin->client = filter_input(INPUT_POST, 'client_id', FILTER_SANITIZE_STRING);
            $UNSAdmin->RenameClient();
            
            $UNSAdmin->Redirect('');
            $UNSAdmin->ShowResults($title);
            break;
        
        
        default:
            $UNSAdmin->GetAllClients();
            $UNSAdmin->smarty->assign("AllClients", $UNSAdmin->client_all_data);
            $UNSAdmin->smarty->display("admin_index.tpl");
            break;
    }
    
}else
{
    $UNSAdmin->smarty->display("admin_login.tpl");
}

?>
