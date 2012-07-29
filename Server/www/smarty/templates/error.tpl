<html>
    <head>
        <title>{$UNS_Title} Critical Error X-(</title>
    </head>
    <body style="background-color: #145285; align: center;">
        <font style="font: Verdana; color: white;">
            <fieldset style="width: 66%; border: 4px solid white; background: #145285;">
                <legend>
                    <b>[</b>UNS Runtime Error: {$UNS_Trace.Error}<b>]</b>
                </legend>
                <table border="0">
                    <tr>
                        <td align="right"><b><u>Message:</u></b></td>
                        <td>{$UNS_Trace.Message}</td>
                    </tr>
                    <tr>
                        <td align="right"><b><u>Code:</u></b></td>
                        <td>{$UNS_Trace.Code}</td>
                    </tr>
                    <tr>
                        <td align="right"><b><u>File:</u></b></td>
                        <td>{$UNS_Trace.File}</td>
                    </tr>
                    <tr>
                        <td align="right"><b><u>Line:</u></b></td>
                        <td>{$UNS_Trace.Line}</td>
                    </tr>
                </table>
            </fieldset>
        </font>
    </body>