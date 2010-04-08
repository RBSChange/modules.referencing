function onInit() {
	this.setWebsiteId();
}

function onLoad() {
	this.setWebsiteId();
}

function onSave() {
	this.getModule().loadRedirectionList();
}

function setWebsiteId() {
	this.getFieldByName('website').value = this.getModule().currentRedirectionWebsiteId;
}
