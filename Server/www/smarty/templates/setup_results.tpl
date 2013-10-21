<html>
<head>
    <title>UNS Setup Results</title>
    <link rel="stylesheet" href="res/styles.css">
    <script src="res/javascript.js"/>
</head>
<body class="main_body" align="center">
<div align="center">
    <table border="1" width="75%" class="main_cell">
        <tr class="client_table_head">
            <th colspan="2">
                UNS Install Process
            </th>
        </tr>
        <tr class="client_table_head">
            <th>
                Step
            </th>
            <th>
                Outcome
            </th>
        </tr>
        <tr>
            <td>Created UNS Tables.</td>
            <td class="{$results.tables.class}">{$results.tables.result}</td>
        </tr>
        <tr>
            <td>Created Internal UNS Admin.</td>
            <td class="{$results.admin_user_create.class}">{$results.admin_user_create.result}</td>
        </tr>
        <tr>
            <td>Created config/vars.php.</td>
            <td class="{$results.config_vars.class}">{$results.config_vars.result}</td>
        </tr>
        <tr>
            <td>Created config/conn.php.</td>
            <td class="{$results.config_conn.class}">{$results.config_conn.result}</td>
        </tr>
        <tr>
            <td colspan="2">If all were successful you can now go on to use your new UNS install. Don't forget to delete the setup.php, setup.sql, and ldap_test.php files.</td>
        </tr>
</body>
</html>
