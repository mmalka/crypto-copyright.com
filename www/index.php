<!DOCTYPE html>
<html>
	<head></head>
	<body>
<h1>Crypto-copyright - In Development</h1>
<p>Crypto Copyright is a service giving the opportunity to an individual or a business entity to certify the existence of a given work or idea at the date of registration using the Bitcoin blockchain and its internal database.<br />
You can also certify the ownership of the work or idea using a raw text hash (only for file).</p>
<h2>Sha-3/224 client-side hashing</h2>
	<input type="file" id="file" name="file" />
	<div id="hash"></div>
    <div id="serverResponse"></div>

	<script src="/js/sha3.js"></script>
	<script src="http://ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js"></script>
	<script>
	$('#file').on('change', function(e) {
		var reader = [],
			crypto,
            hash,
			file;

        $('#hash').html('');
        
        // https://developer.mozilla.org/en-US/docs/Web/API/FileList
        file = e.target.files[0];

        // https://developer.mozilla.org/en-US/docs/Web/API/FileReader
        reader = new FileReader();

        reader.onload = function() {
            crypto = CryptoJS.SHA3(reader.result, { outputLength: 224 });
            hash = crypto.toString();

            $('#hash').append(
                hash + ' ' +
                file.name + ' (' + 
                file.size + ' bytes)<br />'
            );

            // https://developer.mozilla.org/en-US/docs/Web/API/File
            $.post(
                '/server.php',
                {
                    name: file.name,
                    size: file.size,
                    hash: hash
                },
                function(data) {
                    $('#serverResponse').html(data);
                }
            );
        };

        // now start reading
        reader.readAsBinaryString(file);
	});
	</script>

	</body>
</html>