<?php
Class HTML {
	# Setup
	function __construct($DS='seamless') {
		$this->displayName = "<div id='SWAMID-SeamlessAccess'></div>";
		$this->destination = '?first';
		$this->startTimer = time();
		$this->loggedIn = false;
		switch ($DS) {
			case 'seamless' :
				$this->DS = '/DS/seamless-access';
				$this->DSService= '//service.seamlessaccess.org/thiss.js';
				break;
			case 'thiss' :
				$this->DS = '/DS/thiss.io';
				$this->DSService= '//use.thiss.io/thiss.js';
				break;
			default :
				$this->DS = '/DS/seamless-access';
				$this->DSService= '//service.seamlessaccess.org/thiss.js';
		}
	}

	###
	# Print start of webpage
	###
	public function showHeaders($title = "") { ?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title><?=$title?></title>
  <link href="/fontawesome/css/fontawesome.min.css" rel="stylesheet">
  <link href="/fontawesome/css/solid.min.css" rel="stylesheet">
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
  </style>
</head>
<body>
  <div class="container">
    <div class="d-flex flex-column flex-md-row align-items-center p-3 px-md-4 mb-3 bg-white border-bottom box-shadow">
      <h3 class="my-0 mr-md-auto font-weight-normal"><a href="."><img src="https://release-check.swamid.se/swamid-logo-2-100x115.png" width="55"></a> Metadata</h3>
      <nav class="my-2 my-md-0 mr-md-3">
        <a class="p-2 text-dark" href="https://www.sunet.se/swamid/">About SWAMID</a>
        <a class="p-2 text-dark" href="https://www.sunet.se/swamid/kontakt/">Contact us</a>
        <?=$this->loggedIn ? '<a class="p-2 text-dark" href="/admin/?showHelp">Help</a>' : ''?>
      </nav>
      <?=$this->displayName?>

    </div><?php	}
###
# Print footer on webpage
###
public function showFooter($collapseIcons = array(), $seamless = false) {
	$hostURL = "http".(!empty($_SERVER['HTTPS'])?"s":"")."://".$_SERVER['SERVER_NAME'];
	// printf('    <hr>%s    %d%s', "\n", time()-$this->startTimer, "\n");
	?>
  </div><?php if ($seamless) { ?>

  <!-- Include the Seamless Access Sign in Button & Discovery Service -->
  <script src="<?=$this->DSService?>"></script>
  <script>
    window.onload = function() {
      // Render the Seamless Access button
      thiss.DiscoveryComponent({
        loginInitiatorURL: '<?=$hostURL?>/Shibboleth.sso<?=$this->DS?>?target=<?=$hostURL?>/admin/<?=$this->destination?>'
      }).render('#SWAMID-SeamlessAccess');
    };
  </script><?php } ?>

  <!-- jQuery first, then Popper.js, then Bootstrap JS -->
  <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
  <script>
    $(function () {<?php
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
      })<?php		}
	} ?>

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
<?php
	}

	public function setDisplayName($name) {
		$this->displayName = $name;
		$this->loggedIn = true;
	}

	public function setDestination($destination) {
		$this->destination = $destination;
	}
}