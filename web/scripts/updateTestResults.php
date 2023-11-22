<?php
include __DIR__ . '/../html/config.php'; #NOSONAR

try {
  $db = new PDO("mysql:host=$dbServername;dbname=$dbName", $dbUsername, $dbPassword);
  // set the PDO error mode to exception
  $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
  echo "Error: " . $e->getMessage();
}

function parseJson($json) {
  global $db;
  $importFrom = date('Y-m-d H:i', time() - (7 * 24 * 60 * 60)); // 1 week
  $removeBefore = date('Y-m-d H:i', time() - (396 * 24 * 60 * 60)); // 1 year + 1 month

  if ($results = json_decode($json,true)) {
    if (isset($results['objects'])) {
      $resultHandler = $db->prepare(
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
      $resultCleanupHandler = $db->prepare('DELETE FROM TestResults WHERE `time` < :Time');
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
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_HEADER, 0);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
  curl_setopt($ch, CURLOPT_USERAGENT, 'https://metadata.swamid.se/fetcher');

  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

  curl_setopt($ch, CURLINFO_HEADER_OUT, 1);
  curl_setopt($ch, CURLOPT_URL, 'https://release-check.swamid.se/metaDump.php');
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
          print "Got code $http_code from web-server. Cant handle :-(";
          $continue = false;
      }
    }
  }
  curl_close($ch);
}

fetchJson();
