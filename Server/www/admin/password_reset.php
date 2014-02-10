<?php
/**
 * Created by PhpStorm.
 * User: sysferland
 * Date: 10/26/13
 * Time: 9:06 PM
 */



if(@$UNSAdmin->parsed_edit_uri['func'] == 'password_reset')
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

$update_pwd_flag = filter_input(INPUT_POST, "update_password", FILTER_SANITIZE_NUMBER_INT);
$update_pwd_flag_get = filter_input(INPUT_GET, "update_password", FILTER_SANITIZE_NUMBER_INT);
$UNSAdmin->update_pwd = $update_pwd_flag_get;

/* Update the password if it was needed*/
if($update_pwd_flag === 1 && $update_pwd_flag_get === 1)
{
    if($UNSAdmin->UpdatePassword())
    {
        $UNSAdmin->DisplayPage("admin_login.tpl", "Password Update was Successful");
    }
}
