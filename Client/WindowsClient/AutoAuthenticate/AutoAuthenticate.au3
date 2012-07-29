While 1
	If WinExists("Authentication Required") Then
		Send ("{ENTER}")
	EndIf
	Sleep (500)
Wend
