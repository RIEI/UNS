<?php /* Smarty version Smarty-3.1.8, created on 2012-05-30 21:36:52
         compiled from "/var/www/UNS2/smarty/templates/main_index.tpl" */ ?>
<?php /*%%SmartyHeaderCode:11871047494fc6c9f9ccffb9-56460826%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_valid = $_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    'ffbfc757cf57e281ccf4ce0a87c22f2995aaa0d6' => 
    array (
      0 => '/var/www/UNS2/smarty/templates/main_index.tpl',
      1 => 1338428094,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '11871047494fc6c9f9ccffb9-56460826',
  'function' => 
  array (
  ),
  'version' => 'Smarty-3.1.8',
  'unifunc' => 'content_4fc6c9f9d376f9_86205734',
  'variables' => 
  array (
    'UNS_Title' => 0,
    'UNS_Clients_All' => 0,
    'UNS_Client' => 0,
  ),
  'has_nocache_code' => false,
),false); /*/%%SmartyHeaderCode%%*/?>
<?php if ($_valid && !is_callable('content_4fc6c9f9d376f9_86205734')) {function content_4fc6c9f9d376f9_86205734($_smarty_tpl) {?><html>
    <head>
        <title><?php echo $_smarty_tpl->tpl_vars['UNS_Title']->value;?>
</title>
    </head>
    <body style="margin: 0px 0px 0px 0px;" >
        <div align="center">
            <table width="75%">
                <tr>
                    <th>Clients</th>
                </tr>
                <?php  $_smarty_tpl->tpl_vars['UNS_Client'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['UNS_Client']->_loop = false;
 $_from = $_smarty_tpl->tpl_vars['UNS_Clients_All']->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['UNS_Client']->key => $_smarty_tpl->tpl_vars['UNS_Client']->value){
$_smarty_tpl->tpl_vars['UNS_Client']->_loop = true;
?>
                
                <tr>
                    <td>
                        <a href="<?php echo $_smarty_tpl->tpl_vars['UNS_Client']->value[0];?>
" target="_blank"><?php echo $_smarty_tpl->tpl_vars['UNS_Client']->value[1];?>
</a>
                    </td>
                </tr>
            <?php } ?>
</table>
        </div>
    </body>
</html>
<?php }} ?>