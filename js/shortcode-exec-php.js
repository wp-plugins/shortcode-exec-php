function scepro($, result) {
	var e = result.indexOf('=');
	var s = result.substring(0, e);
	var r = result.substring(e + 2, result.length - 1);
	var h = r.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
	$.modal('<div><h1>' + s + '</h1><h3>WYSIWYG</h3>' + r + '<h3>HTML</h3><pre>' + h + '</pre></div>');
}
