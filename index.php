<?php
/*
    Yopp is a very fast way to transfer a file from phone to computer and vice versa.

    author:   Joseph Ernest (twitter: @JosephErnest)
    url:      https://github.com/josephernest/yopp
    license:  MIT license
*/

$maxsize = 50*1024*1024;       // 50 MB
$thefiledata = 'thefiledata';  // file which contains the data
$thefilename = 'thefilename';  // file which contains only the filename
$thetextfile = 'thetextfile';  // file which text from the input
$autoeraseafterdownload = 1;   // delete file after a download
$savetexttimeout = 300;   // debounce milliseconds for text saving

if (isset($_POST['type']) && $_POST['type'] === 'text')
{
    file_put_contents($thetextfile, $_POST['data']);
    die('DONE');
}

if (isset($_POST['type']) && $_POST['type'] === 'upload')
{
    if (empty($_FILES['data']['tmp_name'])) { die('ERROR'); }          // no file sent
    if (!empty($_POST['email'])) { die('ERROR'); }                     // spam honeypot
    if ($_POST['fname'] !== $_FILES['data']['name']) { die('ERROR'); } // someone wants to cheat?
    if ($_FILES['data']['size'] > $maxsize) { die('TOO BIG!'); }       // too big file

    $localfname = $_POST['fname'];

    $data = file_get_contents($_FILES['data']['tmp_name']);

    $file = fopen($thefiledata, 'wb');
    fwrite($file, $data);
    fclose($file);

    $filename = fopen($thefilename, 'wb');
    fwrite($filename, $localfname);
    fclose($filename);

    die('DONE');
}

if (isset($_GET['type']) && $_GET['type'] === 'download')
{
    if (!file_exists($thefiledata) || !file_exists($thefilename))
    {
        echo '<html><head><meta content="width=device-width, initial-scale=1.0" name="viewport"><style type="text/css">*{color:white;font-family:sans-serif;padding:0;margin:0;cursor:pointer;-webkit-touch-callout:none;-webkit-user-select:none;-khtml-user-select:none;-moz-user-select:none;-ms-user-select:none;user-select:none}body{width:100%;top:0;position:absolute;background-color:#f90;height:100%;left:0}a{position:absolute;top:0;height:100%;text-align:center;width:100%;text-decoration:none;display:block}a div{position:relative;top:45%;height:auto;text-align:center;width:100%;font-size:2.5em;text-decoration:none}a span{font-size:70%}</style><title></title></head><body> <a href="./"><div>NO FILE UPLOADED YET<br /><span>CLICK TO RELOAD</span></div></a></body></html>';
        exit;
    }
    $fname = file_get_contents($thefilename);
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $fname . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($thefiledata));
    readfile($thefiledata);

    if ($autoeraseafterdownload)
    {
        unlink($thefiledata);
        unlink($thefilename);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset=utf-8>
<meta name="viewport" content="width=device-width, initial-scale=0.41, maximum-scale=1" />
<title>Yopp</title>
<style type="text/css">
* { color: white; font-family: sans-serif;  padding: 0; margin: 0; cursor: pointer; -webkit-touch-callout: none; box-sizing: border-box; }
#upload { width: 100%; top: 0; position: absolute; background-color: #f44242; height: 33.3%; }
#download { width: 100%; top: 33.3%; position: absolute; background-color: #42a1f4; height: 33.3%; left: 0; }
#text { border: 0; top: 66.6%; width: 100%; height: 33.3%; position: absolute; background-color: #eee; color: black; font-size: 3em; text-align: center; padding-top: 13vh; }
.text { position: absolute; top: 40%; text-align: center; width: 100%; font-size: 3em; -webkit-user-select: none; -khtml-user-select: none; -moz-user-select: none; -ms-user-select: none; user-select: none; }
#email_addr { display:none; }
#file { display:none; }
</style>
</head>
<body>
<input type='file' id='file' />
<div id="upload"><div class="text" id="uploadtext">UPLOAD</div></div>

<?php $text = file_exists($thetextfile) ? file_get_contents($thetextfile) : ''; ?>
<textarea type="text" id="text" placeholder="TEXT"><?php echo $text; ?></textarea>

<a href="index.php?type=download" id="download"><div class="text">DOWNLOAD</div></a>
<input id="email_addr" name="email" size="25" value="" autocomplete="off" />
<script>
var saveTextTimeout;
var upload = document.getElementById('upload');
var uploadtext = document.getElementById('uploadtext');
var fileelt = document.getElementById('file');
var text = document.getElementById('text');

var previousTextValue = text.value;

upload.onclick = function() { fileelt.click(); };

function readfiles(files) {
    if (files[0].size > <?php echo $maxsize; ?>) { uploadtext.innerHTML = 'TOO BIG!'; return; }
    var formData = new FormData();
    formData.append('type', 'upload');
    formData.append('fname', files[0].name);
    formData.append('data', files[0]);
    formData.append('email', document.getElementById('email_addr').value);
    uploadtext.innerHTML = 'BEGINNING UPLOAD';
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '.');
    xhr.onload = function() { uploadtext.innerHTML =  xhr.responseText; };
    xhr.upload.onprogress = function(event) {
        if (event.lengthComputable) {
            var complete = (event.loaded / event.total * 100 | 0);
            uploadtext.innerHTML = 'UPLOADING<br>PROGRESS '+ complete + '%';
        }
    };
    xhr.send(formData);
}

function savetext(text) {
    var request = new XMLHttpRequest();
    request.open('POST', 'index.php', true);
    request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
    request.send('type=text&data=' + text);
}

function updatetext(event) {
    if (previousTextValue === text.value) return;
    clearTimeout(saveTextTimeout);
    saveTextTimeout = setTimeout(function() {
        previousTextValue = text.value;
        savetext(text.value);
    }, <?php echo $savetexttimeout; ?>);
}

document.body.ondragover = function() { uploadtext.innerHTML = 'DROP YOUR FILE HERE'; return false; };
document.body.ondrop = function(e) { e.preventDefault();  readfiles(e.dataTransfer.files); };

fileelt.addEventListener("change", function() { readfiles(fileelt.files); })
text.addEventListener("keyup", updatetext);
text.addEventListener("change", updatetext);
</script>
</body>
</html>
