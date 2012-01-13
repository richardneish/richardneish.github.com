<?php
   require('config.php');
?>
<html>
<head>
<title>Send a text message</title>
<script language="javascript">
function countchars()
{
   message = document.smsform.sms.value.length;
   if (message > 160)
   {
      document.smsform.sms.value = document.smsform.sms.value.substring(0,(160));
      document.smsform.sms.blur();
      rc = false;
   }
   else
   {
      rc = true;
   }
   document.smsform.chars.value = 160 - (message);
   return rc;
}

function validatenum()
{
   number = document.smsform.mobile.value;
   len = document.smsform.mobile.value.length;
   if (len < 1 )
   {
      return;
   }
   if (len <=9 )
   {
      alert("Please check the phone number, it's too short.");
      document.smsform.mobile.focus();
      return;
   }
}
</script>
</head>
<body link=#000000 leftmargin=0 topmargin=0 marginheight=0 marginwidth=0>
<table border=0 width=500><tr><td><b>SEND A MESSAGE</b><br><br>
To send an SMS complete the following form. Use a comma to separate numbers when sending to more than one recipient.  For phone numbers, include the country code (44 for UK, 1 for US, Canada or the Caribbean) but do not include the '+' before the international part of the number or the '0' before the local part of the number.
</TD></TR>
</table>
<p>
<form action="send_sms.php" name="smsform" method="post">
<table width=400 border=0>
<tr>
<td>
<b>
Mobile Number(s) #:
</b>
</td><td>
<input type="text" name="mobile" value="<?= $DEFAULT_MOBILE ?>" onblur="validatenum()" size="16" class="input">
</td></tr>
<tr>
<td>
<b>
Message:
</b>
</td><td>
<textarea name="sms" cols="32" rows="5" onkeyup="countchars()" onchange="countchars()" onblur="countchars()" class="biginput"></textarea>
</td></tr>
<tr><td><b>From-id / Originator:</b></td>
<td><input type="text" name="from_id" value="<?= $from_id ?>" size="15" maxlength="11" class="input"></td></tr>
<tr><td colspan=2>Phone number or text (up to 11 characters)</tr><tr>
<td colspan=2>
<b>
Remaining: <br><input type="text" name="chars" value="160" size="4" maxlength="4" onfocus="document.smsform.sms.focus()" class="input">
</b>
</td>
</tr>
<tr>
<td colspan=2 align=center>
<input type="submit" name="submit" value="Send!" class="input">
<input type="reset" value="Reset" class="input">
</td>
</tr>
</table>
</form>
</BODY>
</HTML>
