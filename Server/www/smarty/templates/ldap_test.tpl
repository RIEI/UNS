</html>
<head>
    <title>UNS LDAP Test page</title>
    <link rel="stylesheet" href="configs/styles.css">
</head>
<body class="main_body" align="center">
<div align="center">
    <form action="?test=test" method="post" enctype="multipart/form-data">
        <table border="1" width="75%" class="main_cell">
            <tr>
                <th>
                    LDAP Server:
                </th>
                <th>
                    <input type="text" name="ldapserver" value=""/>
                </th>
            </tr>
            <tr>
                <th>
                    Domain\Username:
                </th>
                <th>
                    <input type="text" name="user" value=""/>
                </th>
            </tr>
            <tr>
                <th>
                    Password:
                </th>
                <th>
                    <input type="password" name="pwd" value=""/>
                </th>
            </tr>
            <tr>
                <th colspan="2">
                    <input type="submit" value="Submit"/>
                </th>
            </tr>
        </table>
</div>
</body>
</html>