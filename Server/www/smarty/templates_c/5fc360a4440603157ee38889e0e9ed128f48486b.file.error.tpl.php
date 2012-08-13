<?php /* Smarty version Smarty-3.1.8, created on 2012-05-31 18:45:59
         compiled from "/var/www/UNS2/smarty/templates/error.tpl" */ ?>
<?php /*%%SmartyHeaderCode:14005756674fc670ba77e0b9-57673122%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_valid = $_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    '5fc360a4440603157ee38889e0e9ed128f48486b' => 
    array (
      0 => '/var/www/UNS2/smarty/templates/error.tpl',
      1 => 1338428095,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '14005756674fc670ba77e0b9-57673122',
  'function' => 
  array (
  ),
  'version' => 'Smarty-3.1.8',
  'unifunc' => 'content_4fc670ba7f9557_15986736',
  'variables' => 
  array (
    'UNS_Title' => 0,
    'UNS_Trace' => 0,
  ),
  'has_nocache_code' => false,
),false); /*/%%SmartyHeaderCode%%*/?>
<?php if ($_valid && !is_callable('content_4fc670ba7f9557_15986736')) {function content_4fc670ba7f9557_15986736($_smarty_tpl) {?><html>
    <head>
        <title><?php echo $_smarty_tpl->tpl_vars['UNS_Title']->value;?>
 Critical Error X-(</title>
    </head>
    <body style="background-color: #145285; align: center;">
        <font style="font: Verdana; color: white;">
            <fieldset style="width: 66%; border: 4px solid white; background: #145285;">
                <legend>
                    <b>[</b>UNS Runtime Error: <?php echo $_smarty_tpl->tpl_vars['UNS_Trace']->value['Error'];?>
<b>]</b>
                </legend>
                <table border="0">
                    <tr>
                        <td align="right"><b><u>Message:</u></b></td>
                        <td><?php echo $_smarty_tpl->tpl_vars['UNS_Trace']->value['Message'];?>
</td>
                    </tr>
                    <tr>
                        <td align="right"><b><u>Code:</u></b></td>
                        <td><?php echo $_smarty_tpl->tpl_vars['UNS_Trace']->value['Code'];?>
</td>
                    </tr>
                    <tr>
                        <td align="right"><b><u>File:</u></b></td>
                        <td><?php echo $_smarty_tpl->tpl_vars['UNS_Trace']->value['File'];?>
</td>
                    </tr>
                    <tr>
                        <td align="right"><b><u>Line:</u></b></td>
                        <td><?php echo $_smarty_tpl->tpl_vars['UNS_Trace']->value['Line'];?>
</td>
                    </tr>
                </table>
            </fieldset>
        </font>
    </body><?php }} ?>