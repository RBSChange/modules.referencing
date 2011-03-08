function onInit() 
{
	this.getElementById('package-row').setAttribute('collapsed', 'true');
	this.getElementById('baseFileSignature-row').setAttribute('collapsed', 'true');
	this.toolbar.hideCreateButton();
}

function onCreateNew()
{
	this.loadDataFromFile(this.getAttribute('packageName'));	
}

function onSave() 
{
	this.getModule().loadUrlRewritingModuleList();
}

function reloadDefaultRules()
{
	this.loadDataFromFile(this.getFieldByName('package').value);	
}

/**
 * @param String package
 */
function loadDataFromFile(package)
{
	wCore.debug('[urlrewritinginfo_impl::loadDataFromFile] package = '+package);
	if (package)
	{
		var requestUrl = Context.UIBASEURL + "/xul_controller.php?module=" + this.getModule().name + "&action=LoadUrlRewritingInfoForPackage&package=" + package;
		p = new XMLHttpRequest();
		p.onload = null;
		p.open("GET", requestUrl, false);
		p.send(null);
		
		// Strip xml header, because e4x doesn't like it...
		var xmlCode = p.responseText;
		if (xmlCode.indexOf('<?xml') == 0)
		{
			xmlCode = xmlCode.substring(xmlCode.indexOf('>')+1, xmlCode.length);
		}
		var e4x = new XML(xmlCode);
		if (e4x)
		{
			this.getFieldByName('package').value = package;
			var content = e4x.document.component.(@name == 'content').toString();
			this.getFieldByName('content').value = content;
			var baseFileSignature = e4x.document.component.(@name == 'baseFileSignature').toString();
			this.getFieldByName('baseFileSignature').value = baseFileSignature;
		}
	}
	else
	{
		wCore.error('[urlrewritinginfo_impl::loadDataFromFile] No package set!');
	}
}