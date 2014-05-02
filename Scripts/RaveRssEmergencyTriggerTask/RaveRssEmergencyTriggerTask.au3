#Region ;**** Directives created by AutoIt3Wrapper_GUI ****
#AutoIt3Wrapper_Version=Beta
#EndRegion ;**** Directives created by AutoIt3Wrapper_GUI ****
;License Information------------------------------------
;Copyright (C) 2014 Andrew Calcutt
;This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; Version 2 of the License.
;This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
;You should have received a copy of the GNU General Public License along with this program; if not, write to the Free Software Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
;--------------------------------------------------------
;Author = 'Andrew Calcutt'
;Name = 'RaveRssEmergencyTriggerTask.au3'
;Website = 'http://uns.randomintervals.com'
;Function = 'Triggers UNS emergency mode when a new RSS alert is sent from rave (http://www.ravemobilesafety.com/rave-alert/). It will stay in emergency mode for the time set in the config file. Made to be run as a schedualed task, run every minute'
;--------------------------------------------------------
#include <inet.au3>
#include <array.au3>
#include <Date.au3>
#include "UDFs\_XMLDomWrapper.au3"
#include "UDFs\EzMySql_Dll.au3"
#include "UDFs\EzMySql.au3"

Dim $settings = @ScriptDir & '\task_settings.ini'
Dim $DebugLogFile = @ScriptDir & '\DebugLog.txt'
$rss_source = IniRead($settings, 'Settings', 'rss_source', "")
$displaytime = IniRead($settings, 'Settings', 'displaytime', 10)
$MySQL_Server = IniRead($settings, 'Settings', 'MySQL_Server', '')
$MySQL_Port = IniRead($settings, 'Settings', 'MySQL_Port', '3306')
$MySQL_User = IniRead($settings, 'Settings', 'MySQL_User', '')
$MySQL_Pass = IniRead($settings, 'Settings', 'MySQL_Pass', '')
$MySQL_Uns_Database = IniRead($settings, 'Settings', 'MySQL_Uns_Database', 'uns')
$DebugLog = IniRead($settings, 'Settings', 'DebugLog', 0)

_EzMySql_Startup()

;Get Current Date and Time (UTC)
$dt = _Date_Time_GetSystemTime()
$CurrentDateTime = _Date_Time_SystemTimeToDateTimeStr($dt, 1)
_Log('-----------------------------------------------' & @CRLF & 'Current Time:' & $CurrentDateTime & @CRLF)

;Get RSS Alert XML file
$strXML = _INetGetSource($rss_source)
If $strXML <> "" Then
	_XMLLoadXML($strXML, "")

	;Get RSS Alert Date and Time (UTC)
	$datetime_node = "//rss/channel/item/*[6]";$datetime_node = "//rss/channel/item/dc:date" or $datetime_node = "//rss/channel/item/date" did not work. The position will have to due for now untill i find a better way
	$datetime_array = _XMLGetValue($datetime_node)
	$AlertDateTime = StringReplace(StringReplace(StringReplace($datetime_array[1], "T", " "), "Z", ""), "-", "/")
	_Log('Alert Time:' & $AlertDateTime & @CRLF)

	;Get RSS Alert Title
	;$title_node = "//rss/channel/item/title";
	;$title_array = _XMLGetValue($title_node)

	;If RSS Alert timestamp is in display period, turn on emergency mode
	$DateDiff = _DateDiff('n', $AlertDateTime, $CurrentDateTime)
	_Log('Time Diff in Minutes:' & $DateDiff & @CRLF)
	If $DateDiff <= $displaytime Then ;Timestamp is within display period, turn on emergency mode
		_Log('Alert is within display time' & @CRLF)
		If $datetime_array[0] > 0 Then
			_TurnOnEmergencyMode()
		EndIf
	Else ;Timestamp is not within display period, turn on emergency mode
		_Log('Alert is not within display time' & @CRLF)
		_TurnOffEmergencyMode()
	EndIf
Else
	_TurnOffEmergencyMode()
EndIf

_Exit()

Func _TurnOnEmergencyMode()
	$connected = _EzMySql_Open($MySQL_Server, $MySQL_User, $MySQL_Pass, $MySQL_Uns_Database, $MySQL_Port)
	_Log("Connected to mysql:" & $connected & " Error:" & _EzMySql_ErrMsg() & @CRLF)
	If $connected = 1 Then
		;Turn emergency mode on
		_Log('turn on emergency mode' & @CRLF)
		$query = "UPDATE `" & $MySQL_Uns_Database & "`.`settings` SET `emerg`='1';"
		_EzMySql_Exec($query)
		;_EzMySql_Close()
	EndIf
EndFunc   ;==>_TurnOnEmergencyMode

Func _TurnOffEmergencyMode()
	$connected = _EzMySql_Open($MySQL_Server, $MySQL_User, $MySQL_Pass, $MySQL_Uns_Database, $MySQL_Port)
	_Log("Connected to mysql:" & $connected & " Error:" & _EzMySql_ErrMsg() & @CRLF)
	If $connected = 1 Then
		;Turn emergency mode off
		_Log("connected - turn off emergency mode" & @CRLF)
		$query = "UPDATE `" & $MySQL_Uns_Database & "`.`settings` SET `emerg`='0';"
		_EzMySql_Exec($query)
		;_EzMySql_Close()
	EndIf
EndFunc   ;==>_TurnOffEmergencyMode

Func _Exit()
	_SaveSettings()
	_EzMySql_ShutDown()
	Exit
EndFunc   ;==>_Exit

Func _SaveSettings()
	IniWrite($settings, "Settings", "rss_source", $rss_source)
	IniWrite($settings, "Settings", "displaytime", $displaytime)
	IniWrite($settings, "Settings", "MySQL_Server", $MySQL_Server)
	IniWrite($settings, "Settings", "MySQL_Port", $MySQL_Port)
	IniWrite($settings, "Settings", "MySQL_User", $MySQL_User)
	IniWrite($settings, "Settings", "MySQL_Pass", $MySQL_Pass)
	IniWrite($settings, "Settings", "MySQL_Uns_Database", $MySQL_Uns_Database)
	IniWrite($settings, "Settings", "DebugLog", $DebugLog)
EndFunc

Func _Log($msg)
	ConsoleWrite($msg)
	If $DebugLog = 1 Then FileWrite($DebugLogFile, $msg)
EndFunc