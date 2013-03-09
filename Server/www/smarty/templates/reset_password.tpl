{include file="header.tpl"}
            <table align="center">
                <tr>
                    <td align="center">
                        <h2>Password Reset for User: {$username}</h2>
                        <form method="POST" action="?func=password_reset">
                            <input type="hidden" name="password_reset_flag" value="1">
                            <input type="hidden" name="hash" value="{$hash}">
                            <table>
                                <tr>
                                    <td>
                                    New Password:
                                    </td>
                                    <td>
                                        <input type="password" name="password1">
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                    Again:
                                    </td>
                                    <td>
                                        <input type="password" name="password2">
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="2" align="right">
                                        <input type="submit" value="Reset">
                                    </td>
                                </tr>
                            </table>
                            
                        </form>
                    </td>
                </tr>
            </table>
{include file="footer.tpl"}