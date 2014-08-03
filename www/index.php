<!DOCTYPE html>
<html>

<head></head>

<body>
    <div id="margin_left">
        <h1>Crypto-copyright - In Development</h1>
        <p>Crypto Copyright is a service giving the opportunity to an individual or a business entity to certify the existence of a given work or idea at the date of registration using the Bitcoin blockchain and its internal database.
            <br />You can also certify the ownership of the work or idea using a raw text hash (only for file).</p>
        <h2>Sha-3/224 client-side hashing</h2>
    </div>
    <link type="text/css" rel="stylesheet" href="main.css" />
    <script src="/js/sha3.js"></script>
    <script src="http://ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js"></script>

    <div id="calculator">
        <h3>Enter your message text:</h3>
        <p>
            <textarea id="message" name="message"></textarea>
            <br/>
        </p>
        <h4>Choose a hash value bit length:</h4>
        <input type="radio" name="bits" value="224" id="224" checked/>
        <label for="224">224 bits</label>
        <input type="radio" name="bits" value="256" id="256" />
        <label for="256">256 bits</label>
        <input type="radio" name="bits" value="384" id="384" />
        <label for="384">384 bits</label>
        <input type="radio" name="bits" value="512" id="512" />
        <label for="512">512 bits</label>
        <p>
            <input type="button" id="calculate" value="Calculate Hash of Text">
        </p>
    </div>

    <div id="file_calculator">
        <h3>Select your file:</h3>
        <input type="file" id="file" name="file" />
    </div>

    <div id="margin_left">
        <h3>Hash Value</h3>
        <p><span id="hash"></span>
        </p>

    </div>
    <div id="hash"></div>
    <div id="serverResponse"></div>

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
                crypto = CryptoJS.SHA3(reader.result, {
                    outputLength: 224
                });
                hash = crypto.toString();
                $("#post-calculate").show();

                $('#hash').append(
                    hash + ' ' +
                    file.name + ' (' +
                    file.size + ' bytes)<br />'
                );

                // https://developer.mozilla.org/en-US/docs/Web/API/File
                $.post(
                    '/server.php', {
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

    <!-- calculate hash of input text -->
    <script>
        $(document).ready(function() {
            $("#calculate").click(function() {
                var message = $("#message").val();
                var bits = $("input:radio[name='bits']:checked").val();
                var hash = CryptoJS.SHA3(message, {
                    outputLength: parseInt(bits)
                });
                $("#hash").text(hash);
                $("#post-calculate").show();
            });
        });
    </script>

</body>

</html>