/*
filedrag.js - HTML5 File Drag & Drop demonstration
Featured on SitePoint.com
Developed by Craig Buckler (@craigbuckler) of OptimalWorks.net
*/
(function() {

	// getElementById
	function $id(id) {
		return document.getElementById(id);
	}
	// file drag hover
	function FileDragHover(e) {
		e.stopPropagation();
		e.preventDefault();
		e.target.className = (e.type == "dragover" ? "hover" : "");
	}
	// file selection
	function FileSelectHandler(e) {
        var reader = new FileReader();
        FileDragHover(e);
        var file = "";
        if (typeof(e.dataTransfer) !== 'undefined' && typeof(e.dataTransfer.files) !== 'undefined')
            file = e.dataTransfer.files[0];
        if (typeof(e.target) !== 'undefined' && typeof(e.target.files) !== 'undefined')
            file = e.target.files[0];
        reader.onload = function() {
            var crypto = CryptoJS.SHA3(reader.result, { outputLength: 224 });
            hash = crypto.toString();
            window.location.replace("//crypto-copyright.com/hash-builder.php?hash=" + hash + "&name=" + file.name + "&size=" + file.size);
        }
        reader.readAsBinaryString(file);
	}
    var fileselect = $id("fileselect"),
        filedrag = $id("filedrag"),
        notsupported = $id("notsupported"),
        submitbutton = $id("submitbutton");
	function Init() {
		fileselect.addEventListener("change", FileSelectHandler, false);
		var xhr = new XMLHttpRequest();
		if (xhr.upload) {
			filedrag.addEventListener("dragover", FileDragHover, false);
			filedrag.addEventListener("dragleave", FileDragHover, false);
			filedrag.addEventListener("drop", FileSelectHandler, false);
			filedrag.style.display = "block";
            fileselect.style.display = "none";
		}
	}
	if (window.File && window.FileList && window.FileReader) {
		Init();
   	}
    else {
        notsupported.style.display = "block";
        submitbutton.style.display = "none";
        fileselect.style.display = "none";
    }
    filedrag.onclick = function(){fileselect.click();};
})();