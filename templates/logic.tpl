<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en-gb" xml:lang="en-gb">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title>{TITLE}</title>
</head>
<body>
<h1>This is an example template for logic</h1>

<h3>&#123;IF&#125;s</h3>

<p>
{IF T :}
Display this if true.
{ELSE :}
Display if false.
{ENDIF ;}
</p>

<p>
{IF F :}
Display this if true.
{ELSE :}
Display if false.
{ENDIF ;}
</p>

<h3>&#123;FOREACH&#125;s</h3>

<h4>Single list</h4>

<ul>
{FOREACH LIST :}
<li>{FOREACH}</li>
{ENDFOREACH ;}
</ul>

<h4>Nested</h4>

<table>
<tr><th>index 0</th><th>index 1</th></tr>
{FOREACH NESTED :}
<tr><td>{FOREACH 0}</td>
<td>{FOREACH 1}</td></tr>
{ENDFOREACH ;}
</table>

</body>
</html>