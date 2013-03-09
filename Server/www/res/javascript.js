/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

function SetAllCheckBoxes(FormName, FieldName, CheckValue)
{
    var toggle = 'toggle_chk';
    var objToggleCheckBox = document.forms[FormName].elements[toggle];
    if(!document.forms[FormName])
            return;
    var objCheckBoxes = document.forms[FormName].elements[FieldName];

    if(!objCheckBoxes)
            return;
    var countCheckBoxes = objCheckBoxes.length;

    if(!countCheckBoxes)
    {
        objCheckBoxes.checked = objToggleCheckBox.checked;
    }
    else
    {
        // set the check value for all check boxes
        for(var i = 0; i < countCheckBoxes; i++)
        {
            objCheckBoxes[i].checked = objToggleCheckBox.checked;
        }
    }
}

function expandcontract(tbodyid,ClickIcon)
{
    if (document.getElementById(ClickIcon).innerHTML == "+")
    {
        document.getElementById(tbodyid).style.display = "";
        document.getElementById(ClickIcon).innerHTML = "-";
    }else{
        document.getElementById(tbodyid).style.display = "none";
        document.getElementById(ClickIcon).innerHTML = "+";
    }
}