#!/bin/bash
cd /var/www/metadata
cat << EOF > /tmp/swamid.html.$$
	<link href="//release-check.swamid.se/fontawesome/css/fontawesome.min.css" rel="stylesheet">
	<link href="//release-check.swamid.se/fontawesome/css/solid.min.css" rel="stylesheet">
	<link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
	<link rel="apple-touch-icon" sizes="180x180" href="/images/apple-touch-icon.png">
	<link rel="icon" type="image/png" sizes="32x32" href="/images/favicon-32x32.png">
	<link rel="icon" type="image/png" sizes="16x16" href="/images/favicon-16x16.png">
	<link rel="manifest" href="/images/site.webmanifest">
	<link rel="mask-icon" href="/images/safari-pinned-tab.svg" color="#5bbad5">
	<link rel="shortcut icon" href="/images/favicon.ico">
	<meta name="msapplication-TileColor" content="#da532c">
	<meta name="msapplication-config" content="/images/browserconfig.xml">
	<meta name="theme-color" content="#ffffff">
	<style>
/* Space out content a bit */
body {
 padding-top: 20px;
 padding-bottom: 20px;
}

/* Everything but the jumbotron gets side spacing for mobile first views */
.header {
 padding-right: 15px;
 padding-left: 15px;
}

/* Custom page header */
.header {
 border-bottom: 1px solid #e5e5e5;
}
/* Make the masthead heading the same height as the navigation */
.header h3 {
 padding-bottom: 19px;
 margin-top: 0;
 margin-bottom: 0;
 line-height: 40px;
}
.left {
 float:left;
}
.clear {
 clear: both
}

.text-truncate {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    display: inline-block;
    max-width: 100%;
}

/* color for fontawesome icons */
.fa-check {
 color: green;
}

.fa-exclamation-triangle {
 color: orange;
}

.fa-exclamation {
 color: red;
}

/* Customize container */
@media (min-width: 768px) {
.container {
 max-width: 1800px;
}
}
.container-narrow > hr {
 margin: 30px 0;
}

/* Responsive: Portrait tablets and up */
@media screen and (min-width: 768px) {
/* Remove the padding we set earlier */
.header {
 padding-right: 0;
 padding-left: 0;
}
/* Space out the masthead */
.header {
 margin-bottom: 30px;
}
}
	</style>
</head>
<body>
	<div class="container">
		<div class="header">
			<nav>
				<ul class="nav nav-pills float-right">
					<li role="presentation" class="nav-item"><a href="https://www.sunet.se/swamid/" class="nav-link">About SWAMID</a></li>
					<li role="presentation" class="nav-item"><a href="https://www.sunet.se/swamid/kontakt/" class="nav-link">Contact us</a></li>
				</ul>
			</nav>
			<h3 class="text-muted"><a href="."><img src="https://release-check.swamid.se/swamid-logo-2-100x115.png" width="55"></a> Metadata</h3>
		</div>
EOF
cat << EOF > /tmp/swamid-footer.html.$$
		</table>

	</div>
	<!-- jQuery first, then Popper.js, then Bootstrap JS -->
	<script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
  </body>
</html>
</body>
</html>
EOF

cat << EOF > all-idp.html
<html>
<head>
	<meta charset="UTF-8">
	<title>eduGAIN - IdP</title>
EOF
cat /tmp/swamid.html.$$ >> all-idp.html
cat << EOF >>  all-idp.html
		<a href="https://metadata.swamid.se/">Alla i SWAMID</a> | <a href=".?showIdP">IdP i SWAMID</a> | <a href=".?showSP">SP i SWAMID</a> | <b>IdP via interfederation</b> | <a href="https://metadata.swamid.se/all-sp.html">SP via interfederation</a>
		<table class="table table-striped table-bordered">
EOF

cat << EOF > all-sp.html
<html>
<head>
	<meta charset="UTF-8">
	<title>eduGAIN - SP</title>
EOF
cat /tmp/swamid.html.$$ >> all-sp.html
cat << EOF >>  all-sp.html
		<a href="https://metadata.swamid.se/">Alla i SWAMID</a> | <a href=".?showIdP">IdP i SWAMID</a> | <a href=".?showSP">SP i SWAMID</a> | <a href="https://metadata.swamid.se/all-idp.html">IdP via interfederation</a> | <b>SP via interfederation</b>
		<table class="table table-striped table-bordered">
EOF

cat << EOF > dupe-entities.html
<html>
<head>
	<meta charset="UTF-8">
	<title>Duplicate enities</title>
EOF
cat /tmp/swamid.html.$$ >> dupe-entities.html
cat << EOF >>  dupe-entities.html
		<table class="table table-striped table-bordered">
EOF

rm /tmp/swamid.html.$$

wget -q http://mds.swamid.se/md/swamid-2.0.xml -O /tmp/$$.swamid.full
xsltproc ../scripts/temp/idp-summary.xslt /tmp/$$.swamid.full | sed -e 's@#tr@<tr@' -e 's@#br@<br@g' -e 's@#/@</@g' -e 's@##@><@g' -e 's@#@>@g' >> all-idp.html
xsltproc ../scripts/temp/sp-summary.xslt /tmp/$$.swamid.full | sed -e 's@#tr@<tr@' -e 's@#br@<br@g' -e 's@#/@</@g' -e 's@##@><@g' -e 's@#@>@g' >> all-sp.html
grep entityID /tmp/$$.swamid.full | sed 's/.*entityID="//;s/">//' | sort | uniq -c | awk '$1 > 1 { print "<tr><td>" $2 "</td></tr>"}' >> dupe-entities.html
rm /tmp/$$.swamid.full

cat /tmp/swamid-footer.html.$$ >> all-idp.html
cat /tmp/swamid-footer.html.$$ >> all-sp.html
cat /tmp/swamid-footer.html.$$ >> dupe-entities.html

rm /tmp/swamid-footer.html.$$
