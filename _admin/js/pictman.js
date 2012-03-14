/**
 * Picture manager JS functions
 *
 * Drag & drop, event handlers for the picture manager
 *
 * @copyright (C) 2007-2012 Roman Parpalak
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package S2
 */

function str_replace(substr, newsubstr, str)
{
	while (str.indexOf(substr) >= 0)
		str = str.replace(substr, newsubstr);
	return str;
}

var ua = navigator.userAgent.toLowerCase();
var isIE = (ua.indexOf('msie') != -1 && ua.indexOf('opera') == -1);
var isSafari = ua.indexOf('safari') != -1;
var isGecko = (ua.indexOf('gecko') != -1 && !isSafari);

var bIE = document.attachEvent != null;
var bFF = !document.attachEvent && document.addEventListener;

if (bIE)
	attachEvent("onload", Init);
if (bFF)
	addEventListener("load", Init, true);

//=======================[Expanding tree]=======================================

var asExpanded = [];

function ExpandSavedItem (sId)
{
	asExpanded[sId] = true;
}

function SaveExpand ()
{
	var aSpan = document.getElementById("tree_div").getElementsByTagName("SPAN");
	var sPath;

	for (var i = aSpan.length; i-- ;)
		if (sPath = aSpan[i].getAttribute('data-path'))
			asExpanded[sPath] = aSpan[i].parentNode.parentNode.className.indexOf('ExpandOpen') != -1;
}

function LoadExpand ()
{
	var aSpan = document.getElementById("tree_div").getElementsByTagName("SPAN");
	var sPath, eLi;

	for (var i = aSpan.length; i-- ;)
		if ((sPath = aSpan[i].getAttribute('data-path')) && asExpanded[sPath])
		{
			eLi = aSpan[i].parentNode.parentNode;
			eLi.className = str_replace('ExpandClosed', 'ExpandOpen', eLi.className);
		}
}

//=======================[Moving divs]==========================================

var draggableDiv = null, buttonPanel = null;

function InitMovableDivs ()
{
	if (draggableDiv == null) 
	{
		draggableDiv = document.createElement("DIV");
		document.body.appendChild(draggableDiv);
		draggableDiv.setAttribute("id", "dragged");
		MoveDraggableDiv(-99, -99);
	}
	if (buttonPanel == null)
	{
		buttonPanel = document.createElement("SPAN");
		buttonPanel.setAttribute("id", "context_buttons");
		buttonPanel.innerHTML = '<img class="add" src="i/1.gif" onclick="return CreateSubFolder();" alt="' + s2_lang.create_subfolder + '" /><img src="i/1.gif" class="delete" onclick="return DeleteFolder();" alt="' + s2_lang.delete_folder + '" />';
	}
}

function MoveDraggableDiv (x, y)
{
	draggableDiv.style.width = "auto";

	draggableDiv.style.left = x + 10 + "px";
	draggableDiv.style.top = y + 0 + "px";
}

//=======================[Highlight & renaming]=================================

var eHigh = null;
var eFileInfo, eFilePanel;
var sCurDir = '';
var fExecDouble = function () {};

function HighlightItem (item)
{
	if (typeof(item.getAttribute('data-path')) == 'string')
	{
		item.className = "but_panel";
		item.appendChild(buttonPanel);
		GETAsyncRequest(sUrl + 'action=load_items&path=' + encodeURIComponent(item.getAttribute('data-path')), function (http)
		{
			eFilePanel.innerHTML = http.responseText;
			sCurDir = item.getAttribute('data-path');
			document.getElementById('fold_name').innerHTML = "<b>" + (item.innerText ? item.innerText : item.textContent) + "</b>";
		});
	}
	if (item.getAttribute('data-fname'))
	{
		eHigh = item;
		item.className = "but_panel";
		var str = s2_lang.file + sPicturePrefix + item.getAttribute('data-fname');
		if (item.getAttribute('data-fsize'))
			str += "<br />" + s2_lang.value + item.getAttribute('data-fsize');
		if (item.getAttribute('data-dim'))
		{
			var a = item.getAttribute('data-dim').split('*');
			str += "<br />" + s2_lang.size + a[0] + "&times;" + a[1];
			a[2] = sPicturePrefix + item.getAttribute('data-fname');
			fExecDouble = function ()
			{
				if (window.top.ReturnImage)
					window.top.ReturnImage(a[2], a[0],  a[1]);
				else if (opener.ReturnImage)
					opener.ReturnImage(a[2], a[0],  a[1]);
			}
			str += '<br /><input type="button" onclick="fExecDouble(); return false;" value="' + s2_lang.insert + '">';
		}
		else
			fExecDouble = function () {};
		eFileInfo.innerHTML = str;
	}
}

function ReleaseItem ()
{
	if (buttonPanel.parentNode)
	{
		buttonPanel.parentNode.className = "";
		buttonPanel.parentNode.removeChild(buttonPanel);
	}
	if (eHigh)
	{
		eHigh.className = "";
		eHigh = null;
	}
}

var sSavedName = "", eInput;

function RejectName ()
{
	if (sSavedName == "")
		return;

	var eItem = eInput.parentNode;

	if (eItem.nodeName == "SPAN")
	{
		eItem.firstChild.nodeValue = sSavedName;
		sSavedName = "";
		eItem.removeChild(eInput);
	}
	if (eItem.nodeName == "LI")
	{
		eItem.childNodes[2].nodeValue = sSavedName;
		sSavedName = "";
		eItem.removeChild(eInput);
	}
}

function EditItemName (eItem)
{
	if (eItem.nodeName == "SPAN" && eItem.getAttribute('data-path'))
	{
		sSavedName = eItem.firstChild.nodeValue;

		eInput = document.createElement("INPUT");
		eInput.setAttribute("type", "text");
		eInput.onblur = RejectName;

		var KeyDown = function (e)
		{
			var iCode = (e ? e : window.event).keyCode;

			// Escape
			if (iCode == 27 || iCode == 13 && eInput.value == sSavedName)
			{
				RejectName();
				HighlightItem(eItem);
			}
			// Enter
			else if (iCode == 13)
			{
				SaveExpand();
				GETAsyncRequest(sUrl + "action=rename_folder&path=" + encodeURIComponent(eItem.getAttribute('data-path')) + "&name=" + encodeURIComponent(eInput.value), function (http)
				{
					var xmldoc = http.responseXML,
						eUl = eItem.parentNode.parentNode.parentNode;

					eUl.innerHTML = xmldoc.getElementsByTagName('subtree')[0].firstChild.nodeValue;
					eFilePanel.innerHTML = xmldoc.getElementsByTagName('files')[0].firstChild.nodeValue;
					LoadExpand();

					var eSpan = null;
					for (var i = eUl.childNodes.length; i-- ;)
						if (eUl.childNodes[i].childNodes[1].lastChild.getAttribute('selected'))
						{
							eSpan = eUl.childNodes[i].childNodes[1].lastChild;
							break;
						}

					if (eSpan != null)
						HighlightItem(eSpan);

					sSavedName = "";
				});
			}
			else
				setTimeout(function ()
				{
					if (sSavedName)
						eItem.firstChild.nodeValue = '___' + str_replace(' ', ' ', eInput.value);
				}, 0);
		}

		if (isIE || isSafari)
			eInput.onkeydown = KeyDown;
		else
			eInput.onkeypress = KeyDown;
		eInput.value = sSavedName;

		eItem.insertBefore(eInput, eItem.childNodes[1]);
		eInput.focus();
		eInput.select();
		eItem.firstChild.nodeValue += '___';
		ReleaseItem();
	}

	if (eItem.nodeName == "LI")
	{
		sSavedName = eItem.childNodes[1].nodeValue;
		eItem.childNodes[1].nodeValue = "";

		eInput = document.createElement("INPUT");
		eInput.setAttribute("type", "text");
		eInput.onblur = RejectName;
		var KeyDown = function (e)
		{
			var iCode = (e ? e : window.event).keyCode;

			// Escape
			if (iCode == 27 || iCode == 13 && eInput.value == sSavedName)
				RejectName();
			// Enter
			else if (iCode == 13)
			{
				if (eInput.value == sSavedName)
				{
					sSavedName = '';
					return;
				}

				GETAsyncRequest(sUrl + "action=rename_file&path=" + encodeURIComponent(eItem.firstChild.getAttribute('data-fname')) + "&name=" + encodeURIComponent(eInput.value), function (http)
				{
					eFilePanel.innerHTML = http.responseText;
					sSavedName = "";
				});
			}
		}

		if (isIE || isSafari)
			eInput.onkeydown = KeyDown;
		else
			eInput.onkeypress = KeyDown;
		eInput.setAttribute("value", sSavedName);

		eItem.insertBefore(eInput, eItem.childNodes[1]);
		eInput.focus();
		eInput.select();
		eItem.firstChild.onmousedown = null;
	}

}

//=======================[Drag & drop]==========================================

var sourceElement, acceptorElement, sourceParent, sourceFElement;

var dragging = false;

function SetItemChildren (eSpan, sInnerHTML)
{
	var eLi = eSpan.parentNode.parentNode;

	if (eLi.lastChild.nodeName == "UL")
	{
		eLi.lastChild.innerHTML = sInnerHTML;
		eLi.className = str_replace('ExpandClosed', 'ExpandOpen', eLi.className);
	}
	else
	{
		var eUl = document.createElement("UL");
		eLi.className = str_replace('ExpandLeaf', 'ExpandOpen', eLi.className);
		eLi.appendChild(eUl);
		eUl.innerHTML = sInnerHTML;
	}
	ExpandSavedItem(eSpan.getAttribute('data-path'));
}

function SetParentChildren (eParentUl, str)
{
	if (str != "")
		eParentUl.innerHTML = str;
	else
	{
		var eLi = eParentUl.parentNode;
		eLi.removeChild(eLi.lastChild);
		eLi.className = str_replace('ExpandOpen', 'ExpandLeaf', eLi.className);
	}
}

function StartDrag ()
{
	dragging = true;

	if (!sourceElement)
	{
		draggableDiv.innerHTML = sourceFElement.innerHTML;
		draggableDiv.style.visibility = 'visible';
		return;
	}

	ReleaseItem();

	sourceElement.className = 'source';

	draggableDiv.innerHTML = sourceElement.innerHTML;
	draggableDiv.style.visibility = 'visible';

	sourceParent = sourceElement.parentNode.parentNode.parentNode;
}

function StopDrag ()
{
	dragging = false;
	MoveDraggableDiv(-99, -99);
	draggableDiv.style.visibility = "hidden";

	if (acceptorElement)
	{
		if (sourceFElement)
		{
			GETAsyncRequest(sUrl + "action=move_file&spath=" + encodeURIComponent(sourceFElement.getAttribute('data-fname')) + "&dpath=" + encodeURIComponent(acceptorElement.getAttribute('data-path')), function (http)
			{
				eFilePanel.innerHTML = http.responseText;
			});
		}
		else
		{
			SaveExpand();

			var eItem = acceptorElement,
				eLastAcceptor = acceptorElement,
				eSourceLi = sourceElement.parentNode.parentNode,
				bIsLoop = false;

			while (eItem)
			{
				if (eItem == eSourceLi)
				{
					bIsLoop = true;
					break;
				}
				eItem = eItem.parentNode;
			}

			if (bIsLoop)
				alert(s2_lang.no_loops_img);
			else
			{
				GETAsyncRequest(sUrl + "action=drag&spath=" + encodeURIComponent(sourceElement.getAttribute('data-path')) + "&dpath=" + encodeURIComponent(acceptorElement.getAttribute('data-path')), function (http)
				{
					var xmldoc = http.responseXML;
					SetParentChildren(sourceParent, xmldoc.getElementsByTagName('source_parent')[0].firstChild.nodeValue);
					SetItemChildren(eLastAcceptor, xmldoc.getElementsByTagName('destination')[0].firstChild.nodeValue);
					LoadExpand();
				});
			}
		}
		acceptorElement.className = "";
		acceptorElement = null;
	}
}

//=======================[Mouse events]=========================================

var mouseX, mouseY, mouseStartX, mouseStartY;

function MouseDown (e)
{
	var t = window.event ? window.event.srcElement : e.target;

	if (t.nodeName == "IMG")
		if (t.nextSibling)
			t = t.nextSibling;
		else
			t = t.parentNode;

	if (t.nodeName == 'DIV' && t.innerHTML == '')
	{
		// Click on the expand image
		var node = t.parentNode;

		if (node.className.indexOf('ExpandOpen') != -1)
			node.className = str_replace('ExpandOpen', 'ExpandClosed', node.className);
		else if (node.className.indexOf('ExpandClosed') != -1)
			node.className = str_replace('ExpandClosed', 'ExpandOpen', node.className);

		return;
	}
	else if (t.nodeName == "SPAN" && typeof(t.getAttribute('data-path')) == 'string')
		sourceElement = t;
	else if (t.nodeName == "SPAN" && t.getAttribute('data-fname'))
		sourceFElement = t;
	else if (t.nodeName == "LI" && t.firstChild.getAttribute('data-fname'))
		sourceFElement = t.firstChild;
	else
		return;

	var oCanvas = document.getElementsByTagName("HTML")[0];
	mouseStartX = window.event ? event.clientX + oCanvas.scrollLeft : e.pageX;
	mouseStartY = window.event ? event.clientY + oCanvas.scrollTop : e.pageY;

	if (bIE)
	{
		document.attachEvent("onmousemove", MouseMove);
		document.attachEvent("onmouseup", MouseUp);
		document.getElementById('tree_div').attachEvent('onmouseover', MouseIn);
		document.getElementById('tree_div').attachEvent('onmouseout', MouseOut);
		window.event.cancelBubble = true;
		window.event.returnValue = false;
		t.unselectable = true;
	}
	if (bFF)
	{
		document.addEventListener("mousemove", MouseMove, true);
		document.addEventListener("mouseup", MouseUp, true);
		document.getElementById('tree_div').addEventListener('mouseover', MouseIn, false);
		document.getElementById('tree_div').addEventListener('mouseout', MouseOut, false);
		e.preventDefault();
	}
}

function MouseMove (e)
{
	var oCanvas = document.getElementsByTagName("HTML")[0];
	mouseX = window.event ? event.clientX + oCanvas.scrollLeft : e.pageX;
	mouseY = window.event ? event.clientY + oCanvas.scrollTop : e.pageY;

	if (!dragging && (Math.abs(mouseStartY - mouseY) > 5 || Math.abs(mouseStartX - mouseX) > 5))
		StartDrag();

	MoveDraggableDiv(mouseX, mouseY);

	if (bIE)
		window.event.returnValue = false;
	if (bFF)
		e.preventDefault();
}

var idTimer, bIntervalPassed = true;

function MouseUp(e)
{
	if (sSavedName)
		RejectName();

	var is_drop = dragging;
	if (dragging)
		StopDrag();

	if (sourceFElement != null)
	{
		if (!bIntervalPassed)
		{
			// Double click
			fExecDouble();

			clearTimeout(idTimer);
			bIntervalPassed = true;
		}
		else
		{
			// Single click
			if (sourceFElement.className == "but_panel")
			{
				if (!is_drop)
					EditItemName(sourceFElement.parentNode);
			}
			else
			{
				ReleaseItem();
				HighlightItem(sourceFElement);
			}

			bIntervalPassed = false;
			idTimer = setTimeout('bIntervalPassed = true;', 400);
		}

		sourceFElement = null;
	}
	else
	{
		if (sourceElement.className == "but_panel")
			EditItemName(sourceElement);
		else
		{
			ReleaseItem();
			HighlightItem(sourceElement);
		}
		sourceElement = null;
	}

	if (bIE)
	{
		document.detachEvent("onmousemove", MouseMove);
		document.detachEvent("onmouseup", MouseUp);
		document.getElementById('tree_div').detachEvent('onmouseover', MouseIn);
		document.getElementById('tree_div').detachEvent('onmouseout', MouseOut);
	}
	if (bFF)
	{
		document.removeEventListener("mousemove", MouseMove, true);
		document.removeEventListener("mouseup", MouseUp, true);
		document.getElementById('tree_div').removeEventListener('mouseover', MouseIn, false);
		document.getElementById('tree_div').removeEventListener('mouseout', MouseOut, false);
	}
}

// Rollovers

function MouseIn(e)
{
	var t = window.event ? window.event.srcElement : e.target;

	if (t.nodeName == 'SPAN' && typeof(t.getAttribute('data-path')) == 'string' && (sourceElement != null && t != acceptorElement && t != sourceElement || sourceFElement != null))
	{
		acceptorElement = t;
		t.className = "over_far";
	}
}

function MouseOut(e)
{
	var t = window.event ? window.event.srcElement : e.target;

	if (t == acceptorElement)
	{
		t.className = "";
		acceptorElement = null;
	}
}

//=======================[Button handlers]======================================

function DeleteFolder ()
{
	var eSpan = buttonPanel.parentNode;

	if (!confirm(str_replace('%s', eSpan.innerText ? eSpan.innerText : eSpan.textContent, s2_lang.delete_item)))
		return false;

	ReleaseItem();
	GETAsyncRequest(sUrl + "action=delete_folder&path=" + encodeURIComponent(eSpan.getAttribute('data-path')), function (http)
	{
		var eUl = eSpan.parentNode.parentNode.parentNode;
		HighlightItem(eUl.parentNode.childNodes[1].lastChild);
		SaveExpand();
		SetParentChildren(eUl, http.responseText);
		LoadExpand();
		eFilePanel.innerHTML = '';
	});
	return false;
}

function DeleteFile (sName)
{
	if (!confirm(str_replace('%s', sPicturePrefix + sName, s2_lang.delete_file)))
		return;

	GETAsyncRequest(sUrl + "action=delete_file&path=" + encodeURIComponent(sName), function (http)
	{
		eFilePanel.innerHTML = http.responseText;
		eFileInfo.innerHTML = '';
	});
}

function CreateSubFolder ()
{
	var eSpan = buttonPanel.parentNode;
	var eLi = eSpan.parentNode.parentNode;

	ReleaseItem();

	GETAsyncRequest(sUrl + "action=create_subfolder&path=" + encodeURIComponent(eSpan.getAttribute('data-path')), function (http)
	{
		SetItemChildren(eSpan, http.responseText);

		var eItem = null;
		var eUl = eLi.lastChild;
		for (var i = eUl.childNodes.length; i-- ;)
			if (eUl.childNodes[i].childNodes[1].lastChild.getAttribute('selected'))
			{
				eItem = eUl.childNodes[i].childNodes[1].lastChild;
				break;
			}

		if (eItem != null)
		{
			HighlightItem(eItem);
			EditItemName(eItem);
		}
	});
	return false;
}

function Init ()
{
	InitMovableDivs();

	eFileInfo = document.getElementById("finfo");
	eFilePanel = document.getElementById("files");

	// Init tooltips
	if (bIE)
	{
		document.attachEvent("onmouseover", ShowTip);
		document.attachEvent("onmouseout", HideTip);
		document.getElementById('tree_div').attachEvent('onmousedown', MouseDown);
	}
	if (bFF)
	{
		document.addEventListener("mouseover", ShowTip, false);
		document.addEventListener("mouseout", HideTip, false);
		document.getElementById('tree_div').addEventListener('mousedown', MouseDown, false);

		document.getElementById('brd').addEventListener('dragover', function (e)
		{
			e.preventDefault();
		}, false);

		document.getElementById('brd').addEventListener('dragenter', function (e)
		{
			var dt = e.dataTransfer;
			if (!dt)
				return;

			if (dt.types.contains && !dt.types.contains("Files")) //FF
				return;
			if (dt.types.indexOf && dt.types.indexOf("Files") == -1) //Chrome
				return;

			document.getElementById('brd').className = 'accept_drag';
			setTimeout(function () {document.getElementById('brd').className = '';}, 200);
			setTimeout(function () {document.getElementById('brd').className = 'accept_drag';}, 400);
			setTimeout(function () {document.getElementById('brd').className = '';}, 600);
			setTimeout(function () {document.getElementById('brd').className = 'accept_drag';}, 800);
			setTimeout(function () {document.getElementById('brd').className = '';}, 1000);

			e.preventDefault();
		}, false);

		document.getElementById('brd').addEventListener('dragleave', function (e)
		{
			document.getElementById('brd').className = '';
			e.preventDefault();
		}, false);
		document.getElementById('brd').addEventListener('drop', function (e)
		{
			var dt = e.dataTransfer;
			if (!dt || !dt.files)
				return;

			document.getElementById('brd').className = '';

			FileCounter(0, 0);
			var files = dt.files, not_sent = '';
			for (var i = files.length; i-- ;)
				if (files[i].size <= iMaxFileSize)
					SendDroppedFile(files[i]);
				else
					not_sent += '<br />' + files[i].fileName;

			if (not_sent != '')
				PopupMessages.show(str_replace('%s', sFriendlyMaxFileSize, s2_lang.files_too_big) + not_sent);

			e.preventDefault();
		}, false);
	}

	var aeItems = document.getElementById('tree_div').getElementsByTagName('span');
	for (var i = aeItems.length; i-- ;)
		if (typeof(aeItems[i].getAttribute('data-path')) == 'string' && aeItems[i].getAttribute('data-path') == '')
		{
			HighlightItem(aeItems[i]);
			break;
		}
}

var FileCounter = (function (inc, new_value)
{
	var i;

	return function (inc, new_value)
	{
		return (i = (typeof(new_value) == 'number' ? new_value : i + inc));
	}
}());

function DroppedFileUploaded ()
{
	if (this.readyState == 4)
	{
		var s2_status = this.getResponseHeader('X-S2-Status');

		if (s2_status && s2_status != 'Success')
		{
			if (0 == FileCounter(-1))
			{
				SetWait(false);
				if (this.responseText)
					PopupMessages.show(this.responseText);
			}
			return;
		}

		if (this.responseText)
			PopupMessages.show(this.responseText);

		if (0 == FileCounter(-1))
		{
			SetWait(false);
			GETAsyncRequest(sUrl + "action=load_items&path=" + encodeURIComponent(sCurDir), function (http)
			{
				eFilePanel.innerHTML = http.responseText;
			});
		}
	}
}

function SendDroppedFile (file)
{
	var xhr = new XMLHttpRequest();

	FileCounter(1);
	SetWait(true);

	xhr.onreadystatechange = DroppedFileUploaded;

	var data = new FormData();
	data.append('pictures[]', file);
	data.append('dir', sCurDir);
	data.append('ajax', '1');

	xhr.open('POST', sUrl + 'action=upload');
	xhr.send(data);
}

function SetWait (bWait)
{
	document.body.style.cursor = bWait ? 'progress' : 'default';
}

var was_upload = false;

function UploadSubmit (eForm)
{
	eForm.dir.value = sCurDir;
	was_upload = true;
}

function UploadChange (eItem)
{
	var eForm = eItem.form;
	setTimeout(function()
	{
		UploadSubmit(eForm);
		eForm.submit();
	}, 0);
}

function FileUploaded ()
{
	if (!was_upload)
		return;

	var body = window.frames['submit_result'].document.body.innerHTML;

	if (body.replace(/^\s\s*/, "").replace(/\s\s*$/, ""))
		PopupMessages.show(body);

	GETAsyncRequest(sUrl + "action=load_items&path=" + encodeURIComponent(sCurDir), function (http)
	{
		eFilePanel.innerHTML = http.responseText;
		document.getElementById('file_upload_input').innerHTML = document.getElementById('file_upload_input').innerHTML;
	});
}

// Tooltips

function ShowTip (e)
{
	var eItem = window.event ? window.event.srcElement : e.target;
	var title = eItem.getAttribute('title');

	if (!title && eItem.nodeName == "IMG")
	{
		title = eItem.getAttribute('alt');
		eItem.setAttribute('title', title);
	}

	if (title)
		window.status = title;
}

function HideTip (e)
{
	window.status = window.defaultStatus;
}
