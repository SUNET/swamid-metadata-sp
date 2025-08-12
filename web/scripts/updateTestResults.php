<?php
//Load composer's autoloader
require_once __DIR__ . '/../html/vendor/autoload.php';

$config = new \metadata\Configuration();

function parseJson($json) {
  global $config;
  $importFrom = date('Y-m-d H:i', time() - (7 * 24 * 60 * 60)); // 1 week
  $removeBefore = date('Y-m-d H:i', time() - (396 * 24 * 60 * 60)); // 1 year + 1 month

  if ($results = json_decode($json,true)) {
    if (isset($results['objects'])) {
      $resultHandler = $config->getDb()->prepare(
        'INSERT INTO TestResults (`entityID`, `test`, `time`, `result` )
        VALUES (:EntityID, :Test, :Time, :Result)
        ON DUPLICATE KEY UPDATE `time` = :Time, `result` = :Result');
      $resultHandler->bindParam(':EntityID', $entityID);
      $resultHandler->bindParam(':Test', $test);
      $resultHandler->bindParam(':Time', $time);
      $resultHandler->bindParam(':Result', $result);

      foreach ($results['objects'] as $testResult) {
        $time = $testResult['time'];
        if ($time > $importFrom) {
          $entityID = $testResult['entityID']; #NOSONAR used above
          $test = $testResult['test']; #NOSONAR used above
          $result = $testResult['result']; #NOSONAR used above
          $resultHandler->execute();
        }
      }
      $resultCleanupHandler = $config->getDb()->prepare('DELETE FROM TestResults WHERE `time` < :Time');
      $resultCleanupHandler->bindParam(':Time', $removeBefore);
      $resultCleanupHandler->execute();
    } else {
      print "Can't find objects in JSON response";
    }
  } else {
    print "Problem decoding JSON";
  }
}


function fetchJson() {
  global $config;
  $rcURL = $config->getFederation()['releaseCheckResultsURL'];
  if (!$rcURL) {
    print("No release check test results URL configured, skipping test results download.\n");
    return;
  }
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_HEADER, 0);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
  curl_setopt($ch, CURLOPT_USERAGENT, 'https://metadata.swamid.se/fetcher');

  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

  curl_setopt($ch, CURLINFO_HEADER_OUT, 1);

  curl_setopt($ch, CURLOPT_URL, $rcURL);
  $continue = true;
  while ($continue) {
    $output = curl_exec($ch);
    if (curl_errno($ch)) {
      print curl_error($ch);
      $continue = false;
    } else {
      switch ($http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE)) {
        case 200 :
          parseJson($output);
          $continue = false;
          break;
        case 403 :
          print "Access denied. Can't check URL.";
          $continue = false;
          break;
        case 404 :
          print 'Page not found.';
          $continue = false;
          break;
        case 503 :
          print "Service Unavailable. Can't check URL.";
          $continue = false;
          break;
        default :
          print "Got code $http_code from web-server. Can't handle :-(";
          $continue = false;
      }
    }
  }
  curl_close($ch);
}

fetchJson();
