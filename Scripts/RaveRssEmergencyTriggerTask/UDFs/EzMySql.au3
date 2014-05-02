#include-once
#include <EzMySql_Dll.au3>
; #INDEX# =======================================================================================================================
; Title .........: EzMySql
; AutoIt Version : 3.3.6.1
; Language ......: English
; Description ...: Functions that assist access to an MySql database.
; Author(s) .....: Yoriz
; Based on ......: MySQL UDFs working with libmysql.dll by Prog@ndy and the autoit built in Sqlite functionality
; Dll ...........: libmysql.dll or libmySQL_x64.dll

; #CURRENT# =====================================================================================================================
; _EzMySql_Startup
; _EzMySql_ShutDown
; _EzMySql_Open
; _EzMySql_Close
; _EzMySql_Exec
; _EzMySql_GetTable2d
; _EzMySql_AddTable2d
; _EzMySql_Changes
; _EzMySql_ErrMsg
; _EzMySql_FetchData()
; _EzMySql_Query
; _EzMySql_QueryFinalize
; _EzMySql_FetchNames
; _EzMySql_Rows
; _EzMySql_Columns
; _EzMySql_ChangeUser
; _EzMySql_InsertID
; _EzMySql_SelectDB
; ===============================================================================================================================

; #VARIABLES# ===================================================================================================================
Global $hEzMySql_Dll = -1, $hEzMySql_Ptr, $sEzMySql_Result, $sEzMySql_Mutltiline = 1

; struct from MySQL UDFs working with libmysql.dll by Prog@ndy
Global Const $hEzMySql_Field = _
        "ptr name;" & _                 ;/* Name of column */ [[char *
        "ptr orgName;" & _             ;/* Original column name, if an alias */ [[char *
        "ptr table;" & _                ;/* Table of column if column was a field */ [[char *
        "ptr orgTable;" & _            ;/* Org table name, if table was an alias */ [[char *
        "ptr db;" & _                   ;/* Database for table */ [[char *
        "ptr catalog;" & _        ;/* Catalog for table */ [[char *
        "ptr def;" & _                  ;/* Default value (set by mysql_list_fields) */ [[char *
        "ulong length;" & _       ;/* Width of column (create length) */
        "ulong maxLength;" & _   ;/* Max width for selected set */
        "uint nameLength;" & _
        "uint orgNameLength;" & _
        "uint tableLength;" & _
        "uint orgTableLength;" & _
        "uint dbLength;" & _
        "uint catalogLength;" & _
        "uint defLength;" & _
        "uint flags;" & _         ;/* Div flags */
        "uint decimals;" & _      ;/* Number of decimals in field */
        "uint charsetnr;" & _     ;/* Character set */
        "int type;" & _ ;/* Type of field. See mysql_com.h for types */
        "ptr extension;"
; ===============================================================================================================================

; #FUNCTION# ====================================================================================================================
; Name...........: _EzMySql_Startup
; Description ...: Locates or creates the libmysql.dll and creates a MySQL struct
; Syntax.........: _EzMySql_Startup($hEzMySql_DllLoc = "")
; Parameters ....: $hEzMySql_DllLoc - Path to libmysql.dllor libmySQL_x64.dll, if path = "" @scripdir used
;                   | if a path is given and no dll exists it will be created
; Return values .: On Success - 1
; Return values .: On Failure - returns 0 and @error value
;                    1 - Failed to write dll file
;                    2 - Failed to open DLL
;                    3 - Failed to create MySql struct
; Author ........: Yoriz
; Based on script: MySQL UDFs working with libmysql.dll by Prog@ndy
; ===============================================================================================================================
Func _EzMySql_Startup($hEzMySql_DllLoc = "")
    Local $sDll_Filename, $hFileCreate, $hFileWriteOk
    If @AutoItX64 = 0 Then
        $sDll_Filename = "libmysql.dll"
    Else
        $sDll_Filename = "libmySQL_x64.dll"
    EndIf
    If $hEzMySql_DllLoc Then
        If StringRight($hEzMySql_DllLoc, StringLen($sDll_Filename)) <> $sDll_Filename Then
            $hEzMySql_DllLoc = StringRegExpReplace($hEzMySql_DllLoc, "[\\/]+\z", "") & "\"
            $hEzMySql_DllLoc &= $sDll_Filename
        EndIf
    Else
        $hEzMySql_DllLoc = @ScriptDir & "\" & $sDll_Filename
    EndIf
    If Not FileExists($hEzMySql_DllLoc) Then
        $hFileCreate = FileOpen($hEzMySql_DllLoc, 10)
        $hFileWriteOk = FileWrite($hFileCreate, _EzMySql_Dll())
        FileClose($hFileCreate)
        If Not $hFileWriteOk Then Return SetError(1, 0, 0)
    EndIf
    $hEzMySql_Dll = DllOpen($hEzMySql_DllLoc)
    If $hEzMySql_Dll = -1 Then Return SetError(2, 0, 0)
    Local $hPtr = DllCall($hEzMySql_Dll, "ptr", "mysql_init", "ptr", 0)
    If @error Then Return SetError(3, 0, 0)
    $hEzMySql_Ptr = $hPtr[0]
    Return 1
EndFunc   ;==>_EzMySql_Startup

; #FUNCTION# ====================================================================================================================
; Name...........: _EzMySql_Open
; Description ...: Open a MySql Database
; Syntax.........: _EzMySql_Open($Host, $User, $Pass, $Database = "", $Port = 0, $unix_socket = "", $Client_Flag = 0)
; Parameters ....: $Host        - hostname or an IP address
;                  $User        - MySQL login ID
;                  $Pass        - password for user (no password: "" (empty string))
;                  $Database    - default database (no default db: "" (empty string))
;                  $Port        - If port is not 0, the value is used as the port number for the TCP/IP connection.
;                  $unix_socket - specifies the socket or named pipe that should be used. (no pipe: "" (empty string))
;                  $Client_Flag - flags to enable features
; Return values .: On Success - 1
; Return values .: On Failure - returns 0 and @error value
;                    1 - A MySQL struct does not exist
;                    2 - Dll Open Call failed
;                    3 - Database error - check _EzMySql_ErrMsg() for error
; Author ........: Yoriz
; Based on script: MySQL UDFs working with libmysql.dll by Prog@ndy
; ===============================================================================================================================
Func _EzMySql_Open($Host, $User, $Pass, $Database = "", $Port = 0, $unix_socket = "", $Client_Flag = 0)
    If Not $hEzMySql_Ptr Then Return SetError(1, 0, 0)
    Local $PWType = "str", $DBType = "str", $UXSType = "str"
    If $Pass = "" Then $PWType = "ptr"
    If $Database = "" Then $DBType = "ptr"
    If $unix_socket = "" Then $UXSType = "ptr"
    Local $conn = DllCall($hEzMySql_Dll, "ptr", "mysql_real_connect", "ptr", $hEzMySql_Ptr, "str", $Host, "str", $User, $PWType, $Pass, $DBType, $Database, "uint", $Port, $UXSType, $unix_socket, "ulong", $Client_Flag)
    If @error Then Return SetError(2, 0, 0)
    If _EzMySql_ErrMsg() Then Return SetError(3, 0, 0)
    Return 1
EndFunc   ;==>_EzMySql_Open

; #FUNCTION# ====================================================================================================================
; Name...........: _EzMySql_ChangeUser
; Description ...:  Changes the user and causes the database specified by db to become the default (current) database.
; Syntax.........: _EzMySql_ChangeUser($User, $Pass, $Database = "")
; Parameters ....: $User        - MySQL login ID
;                  $Pass        - password for user (no password: "" (empty string))
;                  $Database    - default database (no default db: "" (empty string))
; Return values .: On Success - 1
; Return values .: On Failure - returns 0 and @error value
;                    1 - A MySQL struct does not exist
;                    2 - Dll Open Call failed
;                    3 - Database error - check _EzMySql_ErrMsg() for error
; Author ........: Yoriz
; Based on script: MySQL UDFs working with libmysql.dll by Prog@ndy
; ===============================================================================================================================
Func _EzMySql_ChangeUser($User, $Pass, $Database = "")
    If Not $hEzMySql_Ptr Then Return SetError(1, 0, 0)
    Local $PWType = "str", $DBType = "str"
    If $Pass = "" Then $PWType = "ptr"
    If $Database = "" Then $DBType = "ptr"
    Local $conn = DllCall($hEzMySql_Dll, "int", "mysql_change_user", "ptr", $hEzMySql_Ptr, "str", $User, $PWType, $Pass, $DBType, $Database)
    If @error Then Return SetError(2, 0, 1)
    If _EzMySql_ErrMsg() Then Return SetError(3, 0, 0)
    Return 1
EndFunc   ;==>_EzMySql_ChangeUser

; #FUNCTION# ====================================================================================================================
; Name...........: _EzMySql_SelectDB
; Description ...:  Causes the database specified by db to become the default
; Syntax.........: _EzMySql_SelectDB($Database)
; Parameters ....: $Database    - The new default database name
; Return values .: On Success - 1
; Return values .: On Failure - returns 0 and @error value
;                    1 - A MySQL struct does not exist
;                    2 - Dll Open Call failed
; Author ........: Yoriz
; Based on script: MySQL UDFs working with libmysql.dll by Prog@ndy
; ===============================================================================================================================
Func _EzMySql_SelectDB($Database)
    If Not $hEzMySql_Ptr Then Return SetError(1, 0, 0)
    Local $conn = DllCall($hEzMySql_Dll, "int", "mysql_select_db", "ptr", $hEzMySql_Ptr, "str", $Database)
    If @error Then Return SetError(2, 0, 1)
    Return 1
EndFunc   ;==>_EzMySql_SelectDB

; #FUNCTION# ====================================================================================================================
; Name...........: _EzMySql_Query
; Description ...: Query a single line MySql statement
; Syntax.........: _EzMySql_Query($querystring)
; Parameters ....: $querystring - MySql Statement
; Return values .: On Success - Returns 1
; Return values .: On Failure - returns 0 and @error value
;                    1 - A MySQL struct does not exist
;                    2 - Dll Query Call failed
;                    3 - Database error - check _EzMySql_ErrMsg() for error
;                    4 - Dll Store result call failed
;                    5 - Empty $querystring parameter passed to function
; Author ........: Yoriz
; Based on script: MySQL UDFs working with libmysql.dll by Prog@ndy
; ===============================================================================================================================
Func _EzMySql_Query($querystring)
    If Not $sEzMySql_Mutltiline Then _EzMySql_MultiLine(False)
    If $sEzMySql_Result Then _EzMySql_QueryFinalize()
    If Not $hEzMySql_Ptr Then Return SetError(1, 0, 0)
    If Not $querystring Then Return SetError(5, 0, 0)
    $querystringlength = StringLen($querystring)
    Local $query = DllCall($hEzMySql_Dll, "int", "mysql_real_query", "ptr", $hEzMySql_Ptr, "str", $querystring, "ulong", $querystringlength)
    If @error Then Return SetError(2, 0, 0)
    Local $result = DllCall($hEzMySql_Dll, "ptr", "mysql_store_result", "ptr", $hEzMySql_Ptr)
    If @error Then Return SetError(4, 0, 0)
    If _EzMySql_ErrMsg() Then Return SetError(3, 0, 0)
    $sEzMySql_Result = $result[0]
    Return 1
EndFunc   ;==>_EzMySql_Query

; #FUNCTION# ====================================================================================================================
; Name...........: _EzMySql_QueryFinalize
; Description ...: Finalizes the last query, freeing the allocated memory
; Syntax.........: _EzMySql_QueryFinalize()
; Parameters ....: None
; Return values .: On Success - None
; Return values .: On Failure - returns 0 and @error value
;                    2 - Dll Query Call failed
; Author ........: Yoriz
; Based on script: MySQL UDFs working with libmysql.dll by Prog@ndy
; ===============================================================================================================================
Func _EzMySql_QueryFinalize()
    DllCall($hEzMySql_Dll, "none", "mysql_free_result", "ptr", $sEzMySql_Result)
    If @error Then Return SetError(2, 0, 0)
    $iEzMySql_Rows = 0
    $iEzMySql_Columns = 0
    $sEzMySql_Result = 0
EndFunc   ;==>_EzMySql_QueryFinalize

; #FUNCTION# ====================================================================================================================
; Name...........: _EzMySql_Exec
; Description ...: Executes a MySql query. Can be multi line . does not handle result
; Syntax.........: _EzMySql_Exec($querystring)
; Parameters ....: $querystring - MySql Statement
; Return values .: On Success - Returns 1
; Return values .: On Failure - returns 0 and @error value
;                    1 - A MySQL struct does not exist
;                    2 - Dll Query Call failed
;                    3 - Database error - check _EzMySql_ErrMsg() for error
;                    4 - Dll Store result call failed
;                    5 - Empty $querystring parameter passed to function
; Author ........: Yoriz
Func _EzMySql_Exec($querystring)
    Local $execError, $iNextResult
    If $sEzMySql_Result Then _EzMySql_QueryFinalize()
    If Not $hEzMySql_Ptr Then Return SetError(1, 0, 0)
    If Not $querystring Then Return SetError(5, 0, 0)
    $querystringlength = StringLen($querystring)
    _EzMySql_MultiLine()
    Local $query = DllCall($hEzMySql_Dll, "int", "mysql_real_query", "ptr", $hEzMySql_Ptr, "str", $querystring, "ulong", $querystringlength)
    If @error Then $execError = 2
    If _EzMySql_ErrMsg() Then Return SetError(3, 0, 0)
    Do
        Local $result = DllCall($hEzMySql_Dll, "ptr", "mysql_store_result", "ptr", $hEzMySql_Ptr)
        $sEzMySql_Result = $result[0]
        _EzMySql_QueryFinalize()
        $iNextResult = DllCall($hEzMySql_Dll, "int", "mysql_next_result", "ptr", $hEzMySql_Ptr)
    Until $iNextResult[0] <> 0
    _EzMySql_MultiLine(False)
    If $execError Then Return SetError($execError, 0, 0)
    Return 1
EndFunc   ;==>_EzMySql_Exec

; #FUNCTION# ====================================================================================================================
; Name...........: _EzMySql_Rows
; Description ...: Returns row qty of the last MySql Query
; Syntax.........: _EzMySql_Rows()
; Parameters ....: None
; Return values .: On Success - Returns amount of rows
; Return values .: On Failure - returns -1 and @error value
;                    1 - Dll Rows Call failed
; Author ........: Yoriz
; Based on script: MySQL UDFs working with libmysql.dll by Prog@ndy
; ===============================================================================================================================
Func _EzMySql_Rows()
    Local $aRows = DllCall($hEzMySql_Dll, "uint64", "mysql_num_rows", "ptr", $sEzMySql_Result)
    If @error Then Return SetError(1, 0, -1)
    $iEzMySql_Rows = $aRows[0]
    Return $iEzMySql_Rows
EndFunc   ;==>_EzMySql_Rows

; #FUNCTION# ====================================================================================================================
; Name...........: _EzMySql_Columns
; Description ...: Returns column qty of the last MySql Query
; Syntax.........: _EzMySql_Columns()
; Parameters ....: None
; Return values .: On Success - Returns amount of columns
; Return values .: On Failure - returns -1 and @error value
;                    1 - Dll column Call failed
;                    2 - No result querry to check against
; Author ........: Yoriz
; Based on script: MySQL UDFs working with libmysql.dll by Prog@ndy
; ===============================================================================================================================
Func _EzMySql_Columns()
    If Not $sEzMySql_Result Then Return SetError(2, 0, 0)
    Local $aColumns = DllCall($hEzMySql_Dll, "uint", "mysql_num_fields", "ptr", $sEzMySql_Result)
    If @error Then Return SetError(1, 0, -1)
    $iEzMySql_Columns = $aColumns[0]
    Return $iEzMySql_Columns
EndFunc   ;==>_EzMySql_Columns

; #FUNCTION# ====================================================================================================================
; Name...........: _EzMySql_FetchNames
; Description ...: Returns column names of the last MySql Query
; Syntax.........: _EzMySql_FetchNames()
; Parameters ....: None
; Return values .: On Success - Returns 1d array of column names
; Return values .: On Failure - returns 0 and @error value
;                    1 - Dll column Call failed or Coloumns = 0
;                    2 - Dll column names Call failed
; Author ........: Yoriz
; Based on script: MySQL UDFs working with libmysql.dll by Prog@ndy
; ===============================================================================================================================
Func _EzMySql_FetchNames()
    Local $numberOfFields = _EzMySql_Columns()
    If $numberOfFields < 1 Then Return SetError(1, 0, $numberOfFields)
    Local $fields = DllCall($hEzMySql_Dll, "ptr", "mysql_fetch_fields", "ptr", $sEzMySql_Result)
    If @error Then Return SetError(2, 0, 0)
    $fields = $fields[0]
    Local $struct = DllStructCreate($hEzMySql_Field, $fields)
    Local $arFields[$numberOfFields]
    For $i = 1 To $numberOfFields
        $arFields[$i - 1] = _EzMySql_PtrStringRead(DllStructGetData($struct, 1))
        If $i = $numberOfFields Then ExitLoop
        $struct = DllStructCreate($hEzMySql_Field, $fields + (DllStructGetSize($struct) * $i))
    Next
    Return $arFields
EndFunc   ;==>_EzMySql_FetchNames

; #FUNCTION# ====================================================================================================================
; Name...........: _EzMySql_FetchData
; Description ...: Fetches 1 row of data from the last MySql Query
; Syntax.........: _EzMySql_FetchData()
; Parameters ....: None
; Return values .: On Success - Returns 1d array of row data
; Return values .: On Failure - returns 0 and @error value
;                    1 - no columns found
;                    2 - no rows found
; Author ........: Yoriz
; Based on script: MySQL UDFs working with libmysql.dll by Prog@ndy
; ===============================================================================================================================
Func _EzMySql_FetchData()
    Local $NULLasPtr0 = False

    $fields = _EzMySql_Columns()
    If $fields <= 0 Or $sEzMySql_Result = 0 Then Return SetError(1, 0, 0)

    Local $RowArr[$fields]

    Local $mysqlrow = _EzMySql_Fetch_Row()
    If Not IsDllStruct($mysqlrow) Then Return SetError(2, 0, 0)

    Local $lenthsStruct = _EzMySql_Fetch_Lengths()

    Local $length, $fieldPtr
    For $i = 1 To $fields
        $length = DllStructGetData($lenthsStruct, 1, $i)
        $fieldPtr = DllStructGetData($mysqlrow, 1, $i)
        Select
            Case $length ; if there is data
                $RowArr[$i - 1] = DllStructGetData(DllStructCreate("char[" & $length & "]", $fieldPtr), 1)
            Case $NULLasPtr0 And Not $fieldPtr ; is NULL and return NULL as Ptr(0)
                $RowArr[$i - 1] = Ptr(0)
;~          Case Else ; Empty String or NULL as empty string
                ; Nothing needs to be done, since array entries are default empty string
;~              $RowArr[$i - 1] = ""
        EndSelect
    Next
    Return $RowArr
EndFunc   ;==>_EzMySql_FetchData

; #FUNCTION# ====================================================================================================================
; Name...........: _EzMySql_GetTable2d
; Description ...: Passes out a 2Dimensional array containing Column names and Data of executed Query
; Syntax.........: _EzMySql_GetTable2d($querystring)
; Parameters ....: $querystring - MySql Statement
; Return values .: On Success - Returns 2d array with Column names in index[0] and rows of data
; Return values .: On Failure - returns 0 and @error value
;                    1 - A MySQL struct does not exist
;                    2 - Dll Query Call failed
;                    3 - Database error - check _EzMySql_ErrMsg() for error
;                    4 - Dll Store result call failed
;                    5 - Empty $querystring parameter passed to function
;                    6 - Fetch column names failed
;                    7 - Fetch row qty failed
;                    8 - Fetch column qty failed
; Author ........: Yoriz
; ===============================================================================================================================
Func _EzMySql_GetTable2d($querystring)
    Local $aResult
    Local $QueryResult = _EzMySql_Query($querystring)
    If Not $QueryResult Then Return SetError($QueryResult, 0, 0)
    Local $FetchNameResult = _EzMySql_FetchNames()
    If Not IsArray($FetchNameResult) Then Return SetError(6, 0, 0)
    Local $iRows = _EzMySql_Rows()
    If $iRows = -1 Then Return SetError(7, 0, 0)
    Local $iColumns = _EzMySql_Columns()
    If $iColumns = -1 Then Return SetError(8, 0, 0)
    Local $aResult[$iRows + 1][$iColumns]
    For $i = 0 To $iColumns - 1 Step 1
        $aResult[0][$i] = $FetchNameResult[$i]
    Next
    If $iRows Then
        For $iRowNo = 1 To $iRows
            $aResultFetched = _EzMySql_FetchData()
            For $iColumnNo = 0 To $iColumns - 1 Step 1
                $aResult[$iRowNo][$iColumnNo] = $aResultFetched[$iColumnNo]
            Next
        Next
    EndIf
    Return $aResult
EndFunc   ;==>_EzMySql_GetTable2d

; #FUNCTION# ====================================================================================================================
; Name...........: _EzMySql_AddTable2d
; Description ...: Add an array of data to a speicifed table
; Syntax.........: _EzMySql_AddTable2d($sTableName, $aData, $sDelimeter)
; Parameters ....: $sTableName - Name of the table to add data to
; Parameters ....: $aData      - An array of data to add with the column names in index 0
; Return values .: On Success - Returns 1
; Return values .: On Failure - returns 0 and @error value
;                    1 - A MySQL struct does not exist
;                    2 - Dll Query Call failed
;                    3 - Database error - check _EzMySql_ErrMsg() for error
;                    4 - Dll Store result call failed
;                    5 - Empty $querystring parameter passed to function
;                    6 - Emprty $sTableName parameter passed to function
;                    7 - $aData is not an array
;                    9 - $aData is not a 2d array
; Author ........: Yoriz
Func _EzMySql_AddTable2d($sTableName, $aData)
    Local $querystring, $iResult
    If Not $sTableName Then Return SetError(6, 0, 0)
    If Not IsArray($aData) Then Return SetError(7, 0, 0)
    If Not UBound($aData) > 1 Then Return SetError(8, 0, 0)
    If Not UBound($aData, 2) Then Return SetError(9, 0, 0)
    Local $iColumns = UBound($aData,2)-1
    For $iRow = 1 To UBound($aData)-1
        $querystring &= "INSERT INTO " & $sTableName & " ("
        For $i = 0 To $iColumns Step 1
            $querystring &= $aData[0][$i] & ","
        Next
        $querystring = StringTrimRight($querystring, 1)
        $querystring &= ") VALUES ('"
        For $i = 0 To $iColumns Step 1
            $querystring &= $aData[$iRow][$i] & "','"
        Next
        $querystring = StringTrimRight($querystring, 2)
        $querystring &= ");"
    Next
    If Not _EzMySql_Exec($querystring) Then Return SetError(@error, 0, 0)
    Return 1
EndFunc

; #FUNCTION# ====================================================================================================================
; Name...........: _EzMySql_Changes
; Description ...: After executing a statement returns the number of rows changed
; Syntax.........: _EzMySql_FetchNames()
; Parameters ....: None
; Return values .: On Success - Returns the number of rows changed
; Return values .: On Failure - returns -1 and @error value
;                    1 - Dll column Call failed
;                    2 - A MySQL struct does not exist
; Author ........: Yoriz
; Based on script: MySQL UDFs working with libmysql.dll by Prog@ndy
; ===============================================================================================================================
Func _EzMySql_Changes()
    If Not $hEzMySql_Ptr Then Return SetError(2, 0, -1)
    Local $row = DllCall($hEzMySql_Dll, "uint64", "mysql_affected_rows", "ptr", $hEzMySql_Ptr)
    If @error Then Return SetError(1, 0, -1)
    Return $row[0]
;~  Return __MySQL_ReOrderULONGLONG($row[0])
EndFunc   ;==>_EzMySql_Changes

; #FUNCTION# ====================================================================================================================
; Name...........: _EzMySql_ErrMsg
; Description ...: returns a null-terminated string containing the error message for the most recen function that failed.
; Syntax.........: _EzMySql_ErrMsg()
; Parameters ....: None
; Return values .: On Success - A null-terminated character string that describes the error. An empty string if no error occurred
; Return values .: On Failure - returns 0 and @error value
;                    1 - A MySQL struct does not exist
;                    2 - Dll Call failed
; Author ........: Yoriz
; Based on script: MySQL UDFs working with libmysql.dll by Prog@ndy
; ===============================================================================================================================
Func _EzMySql_ErrMsg()
    If Not $hEzMySql_Ptr Then Return SetError(1, 0, 0)
    Local $errors = DllCall($hEzMySql_Dll, "str", "mysql_error", "ptr", $hEzMySql_Ptr)
    If @error Then Return SetError(2, 0, 0)
    If $errors[0] Then Return $errors[0]
    Return 0
EndFunc   ;==>_EzMySql_ErrMsg

; #FUNCTION# ====================================================================================================================
; Name...........: _EzMySql_InsertID
; Description ...: Returns the value generated for an AUTO_INCREMENT column by the previous INSERT or UPDATE statement.
; Syntax.........: _EzMySql_InsertID()
; Parameters ....: None
; Return values .: On Success - AUTO_INCREMENT columnID
; Return values .: On Failure - returns 0 and @error value
;                    1 - A MySQL struct does not exist
;                    2 - Dll Call failed
; Author ........: Yoriz
; Based on script: MySQL UDFs working with libmysql.dll by Prog@ndy
; ===============================================================================================================================
Func _EzMySql_InsertID()
    If Not $hEzMySql_Ptr Then Return SetError(1, 0, 0)
    Local $row = DllCall($hEzMySql_Dll, "uint64", "mysql_insert_id", "ptr", $hEzMySql_Ptr)
    If @error Then Return SetError(2, 0, 0)
    Return $row[0]
EndFunc   ;==>_EzMySql_InsertID

; #FUNCTION# ====================================================================================================================
; Name...........: _EzMySql_Close
; Description ...: Closes MySql Database
; Syntax.........: _EzMySql_Close()
; Parameters ....: None
; Return values .: On Success - 1
; Return values .: On Failure - returns 0 and @error value
;                    1 - A MySQL struct does not exist
;                    2 - Dll Call failed
; Author ........: Yoriz
; Based on script: MySQL UDFs working with libmysql.dll by Prog@ndy
; ===============================================================================================================================
Func _EzMySql_Close()
    If Not $hEzMySql_Ptr Then Return SetError(1, 0, 0)
    If $sEzMySql_Result Then _EzMySql_QueryFinalize()
    DllCall($hEzMySql_Dll, "none", "mysql_close", "ptr", $hEzMySql_Ptr)
    If @error Then Return SetError(2, 0, 0)
    Return 1
EndFunc   ;==>_EzMySql_Close

; #FUNCTION# ====================================================================================================================
; Name...........: _EzMySql_ShutDown
; Description ...: Closes MySQL DLL to free memory used by MySQL and closes Dll
; Syntax.........: _EzMySql_ShutDown()
; Parameters ....: None
; Return values .: None
; Author ........: Yoriz
; Based on script: MySQL UDFs working with libmysql.dll by Prog@ndy
; ===============================================================================================================================
Func _EzMySql_ShutDown()
    DllCall($hEzMySql_Dll, "none", "mysql_server_end")
    DllClose($hEzMySql_Dll)
    $hEzMySql_Ptr = 0
    $hEzMySql_Dll = 0
EndFunc   ;==>_EzMySql_ShutDown

; #FUNCTION# ====================================================================================================================
; Name...........: _EzMySql_MultiLine
; Description ...: Allow multiple statements in a single string (separated by “;”).
; Syntax.........: _EzMySql_MultiLine($fBol = True)
; Parameters ....: $fBol - True = on, False = off
; Return values .: On Success - 1
; Return values .: On Failure - returns 0 and @error value
;                    1 - A MySQL struct does not exist
;                    2 - Dll Call failed
; Author ........: Yoriz
; Based on script: MySQL UDFs working with libmysql.dll by Prog@ndy
; ===============================================================================================================================
Func _EzMySql_MultiLine($fBol = True)
    $sEzMySql_Mutltiline = 1
    If $fBol Then $sEzMySql_Mutltiline = 0
    If Not $hEzMySql_Ptr Then Return SetError(1, 0, 0)
    Local $mysql = DllCall($hEzMySql_Dll, "int", "mysql_set_server_option", "ptr", $hEzMySql_Ptr, "dword", $sEzMySql_Mutltiline)
    If @error Then Return SetError(2, 0, 0)
    Return 1
EndFunc   ;==>_EzMySql_MultiLine

; #FUNCTION# ====================================================================================================================
; Name...........: _EzMySql_Fetch_Row
; Description ...: Retrieves the next row of a result set.
; Syntax.........: _EzMySql_Fetch_Row()
; Parameters ....: None
; Return values .: On Success - DLLStruct with pointers to data fields
; Return values .: On Failure - returns 0 and @error value
;                    1 - Dll column Call failed or Coloumns = 0
;                    2 - Dll fetch row Call failed
; Author ........: Yoriz
; Based on script: MySQL UDFs working with libmysql.dll by Prog@ndy
; ===============================================================================================================================
Func _EzMySql_Fetch_Row()
    Local $numberOfFields = _EzMySql_Columns()
    If $numberOfFields <= 0 Then Return SetError(2, 0, 0)
    Local $row = DllCall($hEzMySql_Dll, "ptr", "mysql_fetch_row", "ptr", $sEzMySql_Result)
    If @error Then Return SetError(1, 0, 0)
    Return DllStructCreate("ptr[" & $numberOfFields & "]", $row[0])
EndFunc   ;==>_EzMySql_Fetch_Row

; #FUNCTION# ====================================================================================================================
; Name...........: _EzMySql_Fetch_Lengths
; Description ...: Returns the lengths of the columns of the current row within a result set.
; Syntax.........: _EzMySql_Fetch_Lengths()
; Parameters ....: None
; Return values .: On Success - DLLStruct with ulong Array get data [ DLLStructGetData($struct,1, $n ) ]
; Return values .: On Failure - returns 0 and @error value
;                    1 - Dll column Call failed or Coloumns = 0
;                    2 - Dll fetch length Call failed
; Author ........: Yoriz
; Based on script: MySQL UDFs working with libmysql.dll by Prog@ndy
; ===============================================================================================================================
Func _EzMySql_Fetch_Lengths()
    Local $numberOfFields = _EzMySql_Columns()
    If $numberOfFields <= 0 Then Return SetError(1, 0, 0)
    Local $lengths = DllCall($hEzMySql_Dll, "ptr", "mysql_fetch_lengths", "ptr", $sEzMySql_Result)
    If @error Then Return SetError(2, 0, 0)
    Return DllStructCreate("ulong lengths[" & $numberOfFields & "]", $lengths[0])
EndFunc   ;==>_EzMySql_Fetch_Lengths

;===============================================================================
; Function Name:   _EzMySql_PtrStringRead
; Description::    Reads a string by pointer
; Parameter(s):    $ptr       - Pointer to String
;                  $IsUniCode - Is a unicode string default. False
; Requirement(s):  libmysql.dll
; Return Value(s): read string
; Author(s):       Prog@ndy
;===============================================================================
Func _EzMySql_PtrStringRead($ptr, $IsUniCode = False, $StringLen = -1)
    Local $UniCodeString = ""
    If $IsUniCode Then $UniCodeString = "W"
    If $StringLen < 1 Then $StringLen = _EzMySql_PtrStringLen($ptr, $IsUniCode)
    If $StringLen < 1 Then Return SetError(1, 0, "")
    Local $struct = DllStructCreate($UniCodeString & "char[" & ($StringLen + 1) & "]", $ptr)
    Return DllStructGetData($struct, 1)
EndFunc   ;==>_EzMySql_PtrStringRead

;===============================================================================
; Function Name:   _EzMySql_PtrStringLen
; Description::    Gets length for a string by pointer
; Parameter(s):    $ptr       - Pointer to String
;                  $IsUniCode - Is a unicode string default. False
; Requirement(s):  libmysql.dll
; Return Value(s): Length of the string
; Author(s):       Prog@ndy
;===============================================================================
Func _EzMySql_PtrStringLen($ptr, $IsUniCode = False)
    Local $UniCodeFunc = ""
    If $IsUniCode Then $UniCodeFunc = "W"
    Local $Ret = DllCall("kernel32.dll", "int", "lstrlen" & $UniCodeFunc, "ptr", $ptr)
    If @error Then Return SetError(1, 0, -1)
    Return $Ret[0]
EndFunc   ;==>_EzMySql_PtrStringLen