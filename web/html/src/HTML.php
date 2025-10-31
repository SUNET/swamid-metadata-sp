<?php
namespace metadata;

/**
 * Class to handle printing of Header and Footer of web-pages
 */
class HTML {
  # Setup
  private string $displayName = '';
  private string $destination = '';
  private bool $loggedIn = false;
  private array $tableToSort = array();
  private bool $showDownload = true;
  private string $mode = '';
  private Configuration $config;
  private array $federation = array();

  /**
   * Setup the class
   *
   * @return void
   */
  public function __construct() {
    global $config;
    $this->displayName = '<div class="d-flex sa-button" role="button">
        <div class="sa-button-logo-wrap">
          <img src="https://service.seamlessaccess.org/sa-white.svg" class="sa-button-logo" alt="Seamless Access Logo"/>
        </div>
        <div class="d-flex justify-content-center align-items-center sa-button-text text-truncate">
          <div class="sa-button-text-primary text-truncate">Access through your institution</div>
        </div>
      </div>';
    if (isset($config)) {
      $this->config = $config;
    } else {
      $this->config = new Configuration();
    }
    $this->mode = $this->config->getMode();
    $this->federation = $this->config->getFederation();
  }

  /**
   * Print start of webpage
   *
   * @param string $title_part String to be added in title
   *
   * @return void
   */
  public function showHeaders($title_part = "") { ?>
<!DOCTYPE html>
<html lang="en" xml:lang="en">
<head>
  <meta charset="UTF-8">
  <title><?=htmlspecialchars($this->getPageTitle($title_part))?></title>
  <link href="/fontawesome/css/fontawesome.min.css" rel="stylesheet">
  <link href="/fontawesome/css/solid.min.css" rel="stylesheet">
  <link href="/fontawesome/css/regular.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css"
    integrity="sha384-xOolHFLEh07PJGoPkLv1IbcEPTNtaed2xpHsD9ESMhqIYd0nLMwNLD69Npy4HI+N" crossorigin="anonymous">
  <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.12.1/css/jquery.dataTables.css">
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
      <?= $this->mode == 'QA' ?
        'background-color: #F05523;' :
        ''?><?= $this->mode == 'Lab' ? 'background-color: #8B0000;' : ''?>
    }

    .container {
      <?= ($this->mode == 'QA' || $this->mode == 'Lab') ? 'background-color: #FFFFFF;' : ''?>
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
    .fa-exclamation-triangle,
    .fa-clock {
      color: orange;
    }
    .fa-exclamation,
    .fa-bell {
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
    /* SA Button */
    .sa-button, .sa-access-text {
      font-family: Arial, sans-serif;
      line-height: 1.4;
    }
    .sa-button {
      cursor: pointer;
      background-color: #F05523;
      border-radius: 5px;
      padding: 9px;
    }
    .sa-button-logo-wrap {
      text-align: center;
      width: 50px;
      height: 100%;
      border-right: 1px solid var(--white);
      padding: 5px 5px 5px 0;
    }
    .sa-button-logo {
      width: 30px;
      vertical-align: middle;
    }
    .sa-button-text {
      padding-left: 10px;
      text-align: center;
      width: 85%;
      color: var(--white);
    }
    .sa-button-text-primary {
      font-size: 14px;
      font-weight: 700;
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="d-flex flex-column flex-md-row align-items-center p-3 px-md-4 mb-3 bg-white border-bottom box-shadow">
      <h3 class="my-0 mr-md-auto font-weight-normal">
        <a href=".">
          <img src="<?= $this->federation['logoURL'] ?>" alt="<?= $this->federation['displayName'] ?> Logo" width="<?= $this->federation['logoWidth'] ?>" height="<?= $this->federation['logoHeight'] ?>">
        </a> Metadata <?= $this->mode == 'Prod' ? '' : $this->mode?>

      </h3>
      <nav class="my-2 my-md-0 mr-md-3">
        <a class="p-2 text-dark" href="<?= $this->federation['aboutURL'] ?>">About <?= $this->federation['displayName'] ?></a>
        <a class="p-2 text-dark" href="<?= $this->federation['contactURL'] ?>">Contact us</a>
        <?=$this->loggedIn ? '<a class="p-2 text-dark" href="/admin/?showHelp">Help</a>' : ''?>

      </nav>
      <a href="/admin/<?=$this->destination?>"><?=$this->displayName?></a>
    </div>
<?php }
  /**
   * Print footer of webpage
   *
   * @param array $collapseIcons Array of icons to add script for (un)collapse in footer
   *
   * @return void
   */
  public function showFooter($collapseIcons = array()) {
    print "\n  </div>";
    printf('%s  <!-- jQuery first, then Popper.js, then Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/jquery@3.5.1/dist/jquery.slim.min.js"
    integrity="sha384-DfXdz2htPH0lsSSs5nCTpuj/zy4C+OGpamoFVy38MVBnE+IbbVYUew+OrCXaRkfj"
    crossorigin="anonymous">
  </script>
  <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"
    integrity="sha384-9/reFTGAW83EW2RDu2S0VKaIzap3H66lZH81PoYlFhbGU+6BZp6G7niu735Sk7lN"
    crossorigin="anonymous">
  </script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.min.js"
    integrity="sha384-+sLIOodYLS7CIrQpBjl+C7nPvqq+FbNUBDunl/OZv93DB7Ln/533i8e/mZXLi/P+"
    crossorigin="anonymous">
  </script>%s', "\n", "\n");
    if (isset($this->tableToSort[0]) || isset($collapseIcons[0]) || $this->showDownload ) {
      if (isset($this->tableToSort[0])) {
        # Add JS script to be able to use later
        printf('  <script type="text/javascript" charset="utf8"
      src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.js"></script>%s', "\n");
      }
      print "  <script>\n";
      if (isset($collapseIcons[0])) {
        print "    $(function () {";
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
      })<?php
      }
      print "    })\n";
      }

      # Add function to sort if needed
      if (isset($this->tableToSort[0])) {
        print "    $(document).ready(function () {\n";
        foreach ($this->tableToSort as $table) {
          printf ("      $('#%s').DataTable( {paging: false});\n", $table);
        }
        print "    });\n";

      }
    ?>
    // Add the following code if you want the name of the file appear on select
    $(".custom-file-input").on("change", function() {
      //var fileName = $(this).val().split("\\").pop();
      var fileName = $(this).val().split("\\\\").pop();
      $(this).siblings(".custom-file-label").addClass("selected").html(fileName);
    });<?php
      print "\n  </script>\n";
    }
    print "</body>\n</html>";
  }

  /**
   * Set/change DisplayName
   *
   * @param string $name Info to show instead of login button
   *
   * @return void
   */
  public function setDisplayName($name) {
    $this->displayName = $name;
    $this->loggedIn = true;
  }

  /**
   * Set/change Destination
   *
   * @param string $destination Destination after login
   *
   * @return void
   */
  public function setDestination($destination) {
    $this->destination = $destination;
  }

  /**
   * Add table that should be sorted
   *
   * Added as script/DataTable when footer is generated.
   *
   * @return void
   */
  public function addTableSort($tableId) {
    $this->tableToSort[] = $tableId;
  }

  /**
   * Creates the title
   *
   * @param string $title_part String to add in title
   *
   * @return string
   */
  private function getPageTitle($title_part) {
    return 'Metadata ' . $this->federation['displayName'] . ( $title_part ? ' - ' . $title_part : '');
  }

  /**
   * Helper function to return a base URL based on a given URL (strip out path and query string)
   *
   * @param string $url The URL to process.
   *
   * @return string Base URL of $url
   */
  public function getBaseURL($url) {
    return preg_replace(',^(https?://[^/]+/).*$,', '\1', $url);
  }
}
