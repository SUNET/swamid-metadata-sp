<?php
Class HTML {
	# Setup
	function __construct() {
	}

	###
	# Print start of webpage
	###
	function showHeaders($title = "") {
?>
<html>
<head>
  <meta charset="UTF-8">
  <title><?=$title?></title>
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
<?php	}

	###
	# Print footer on webpage
	###
	function showFooter($collapseIcons = array()) {
		echo <<<EOF
  </div>
  <!-- jQuery first, then Popper.js, then Bootstrap JS -->
  <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
  <script>
    $(function () {

EOF;
if (isset($collapseIcons)) {
  foreach ($collapseIcons as $collapseIcon) { ?>
      $('#<?=$collapseIcon?>').on('show.bs.collapse', function (event) {
        var tag_id = document.getElementById('<?=$collapseIcon?>-icon');
        tag_id.className = "fas fa-chevron-circle-down";
        event.stopPropagation();
      })
      $('#<?=$collapseIcon?>').on('hide.bs.collapse', function (event) {
        var tag_id = document.getElementById('<?=$collapseIcon?>-icon');
        tag_id.className = "fas fa-chevron-circle-right";
        event.stopPropagation();
      })
<?php }
}
echo <<<EOF
    })
    // Add the following code if you want the name of the file appear on select
    $(".custom-file-input").on("change", function() {
      //var fileName = $(this).val().split("\\").pop();
      var fileName = $(this).val().split("\\\\").pop();
      $(this).siblings(".custom-file-label").addClass("selected").html(fileName);
    });
  </script>
</body>
</html>
EOF;
	}
}

