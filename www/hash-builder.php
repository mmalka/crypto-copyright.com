<?php
    if (isset($_GET['hash']))
    {
        var_dump($_GET);
    }
    else
    {
?>
        <link rel="stylesheet" type="text/css" media="all" href="./css/styles.css" />
        <form id="upload" action="index.html" method="POST" enctype="multipart/form-data">

        <div id="hash-builder">
            <input type="hidden" id="MAX_FILE_SIZE" name="MAX_FILE_SIZE" value="300000" />
            <input type="file" type="hidden" id="fileselect" name="fileselect[]" multiple="multiple" />
            <div id="filedrag">Click or Drag and drop your document here.<br /> Your document will NOT be uploaded. The cryptographic proof is calculated client-side. </div>
            <div id="notsupported">Your browser don't support client-side file reading.<br /> For your security, we don't allow uploading files to our server, please create the SHA-3/224 digest of your file yourself. You can use our software if you don't know how to proceed.</div>
        </div>
        </form>
        <script src="/js/sha3.js"></script>
        <script src="./js/filedrag.js"></script>
<?php
    }
?>