<?php

// Modify the paths to these class files as needed.
require_once("class_http.php");
require_once("class_xml.php");

// Change these values for your Exchange Server.
$exchange_server = "https://mail1.rulefinancial.com";
$exchange_username = "rneish";
$exchange_password = "password09";
$exchange_alias = "richard.neish";

// We use Troy's http class object to send the XML-formatted WebDAV request
// to the Exchange Server and to receive the response from the Exchange Server.
// The response is also XML-formatted.
$h = new http();

// Log in using Form-Based Authentication
$h->postvars["destination"]="$exchange_server/exchange/";
$h->postvars["flags"]="0";
$h->postvars["username"]=$exchange_username;
$h->postvars["password"]=$exchange_password;
if (!$h->fetch("$exchange_server/exchweb/bin/auth/owaauth.dll")) {
  echo "<h2>There is a problem with the http request!</h2>";
  echo $h->log;
  exit();
}
$cookies="";
foreach (preg_split("/\r\n/", $h->header) as $header) {
  $pattern = "/Set-Cookie: ([^;]*)/";
  preg_match($pattern,$header,$matches);
  if (trim($matches[1]) != "") {
    if ($cookies != "") {
      $cookies .= "; ";
    }
    $cookies .= $matches[1];
  }
}

// Now connect to the mailbox.
$h = new http();
$h->headers["Cookie"] = $cookies;
$h->headers["Content-Type"] = 'text/xml; charset="UTF-8"';

// http://msdn.microsoft.com/library/default.asp?url=/library/en-us/e2k3/e2k3/_webdav_depth_header.asp
$h->headers["Depth"] = "0";

$h->headers["Translate"] = "f";

// The trickiest part is forming your WebDAV query. This example shows how to
// find all the folders in the inbox for the exchange user.
$h->xmlrequest = '<?xml version="1.0"?>';
$h->xmlrequest .= <<<END
<a:searchrequest xmlns:a="DAV:" xmlns:s="http://schemas.microsoft.com/exchange/security/">
   <a:sql>
       SELECT "DAV:displayname"
       FROM SCOPE('hierarchical traversal of "$exchange_server/exchange/$exchange_alias/Inbox/"')
   </a:sql>
</a:searchrequest>
END;
// IMPORTANT -- The END line above must be completely left-aligned. No white-space.

// The 'fetch' method does the work of sending and receiving the request.
// NOTICE the last parameter passed--'SEARCH' in this example. That is the
// HTTP verb that you must correctly set according to the type of WebDAV request
// you are making.  The examples on this page use either 'PROPFIND' or 'SEARCH'.
if (!$h->fetch("$exchange_server/exchange/$exchange_alias/Inbox/", 0, null, null, null, "SEARCH")) { 
  echo "<h2>There is a problem with the http request!</h2>";
  echo $h->log;
  exit();
}

// Note: The following lines can be uncommented to aid in debugging.
#echo "<pre>".$h->log."</pre><hr />\n";
#echo "<pre>".$h->header."</pre><hr />\n";
#echo "<pre>".$h->body."</pre><hr />\n";
#exit();
// Or, these next lines will display the result as an XML doc in the browser.
#header('Content-type: text/xml');
#echo $h->body;
#exit();

// The assumption now is that we've got an XML result back from the Exchange
// Server, so let's parse the XML into an object we can more easily access.
// For this task, we'll use Troy's xml class object.
$x = new xml();
if (!$x->fetch($h->body)) {
    echo "<h2>There was a problem parsing your XML!</h2>";
    echo "<pre>".$h->log."</pre><hr />\n";
    echo "<pre>".$h->header."</pre><hr />\n";
    echo "<pre>".$h->body."</pre><hr />\n";
    echo "<pre>".$x->log."</pre><hr />\n";
    exit();
}

// You should now have an object that is an array of objects and arrays that
// makes it easy to access the parts you need. These next lines can be
// uncommented to make a raw display of the data object.
#echo "<pre>\n";
#print_r($x->data);
#echo "</pre>\n";
#exit();

// And finally, an example of iterating the inbox folder names and url's to
// display in the browser. I also show you 2 methods to link to the folders.
// One uses the href provided in the response which opens the folder using OWA.
// The other is an Outlook style link to open the folder in the Outlook desktop
// client.
echo '<table border="1">';
foreach($x->data->A_MULTISTATUS[0]->A_RESPONSE as $idx=>$item) {
    echo '<tr>'
        .'<td>'.$item->A_PROPSTAT[0]->A_PROP[0]->A_DISPLAYNAME[0]->_text.'</td>'
        .'<td><a href="'.$item->A_HREF[0]->_text.'">Click to open via OWA</a></td>'
        .'<td><a href="Outlook:Inbox/'.$item->A_PROPSTAT[0]->A_PROP[0]->A_DISPLAYNAME[0]->_text.'">Click to open via Outlook</a></td>'
        ."</tr>\n";
}
echo "<table>\n";

?> 