<?php
/*
  Copyright 2014-2018 Melin Software HB
  
  Licensed under the Apache License, Version 2.0 (the "License");
  you may not use this file except in compliance with the License.
  You may obtain a copy of the License at
  
      http://www.apache.org/licenses/LICENSE-2.0
  
  Unless required by applicable law or agreed to in writing, software
  distributed under the License is distributed on an "AS IS" BASIS,
  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
  See the License for the specific language governing permissions and
  limitations under the License.
  */


include_once('functions.php');
session_start();
header('Content-type: application/json;charset=utf-8');


$PHP_SELF = $_SERVER['PHP_SELF'];
$REMOTE_IP = $_SERVER['REMOTE_ADDR'];
$link = ConnectToDB();




if (isset($_GET['cmp'])) {
  $cmpId = $_GET['competition'];
  $sql = "SELECT * FROM mopCompetition WHERE cid = '$cmpId'";
} else {
  $sql = "SELECT * FROM mopCompetition WHERE 1 ORDER BY DATE DESC LIMIT 1";
}
$res = $link->query($sql);

if ($r = $res->fetch_assoc()) {
  $cmpId = $r['cid'];

  $eventconfig = array(
    "eventname" => $r['name']
  );
}


$sql = "SELECT cc.* FROM classesClients AS cc, clients AS c WHERE cc.client_id = c.id AND c.ip='$REMOTE_IP' AND c.cid=$cmpId";
$resClasses = $link->query($sql);

if ($resClasses->num_rows > 0) {
  $exists_client = true;
} else {
  $exists_client = false;
}


if (isset($exists_client)) {
  $sql = "SELECT cls.id AS classId, cls.name AS name, cc.leg AS leg FROM clients AS c, classesClients AS cc, mopClass AS cls WHERE c.ip='$REMOTE_IP' AND c.cid=$cmpId AND cc.client_id=c.id AND cls.id=cc.class_id  ORDER BY cls.ord";
} else {
  $sql = "SELECT cls.id AS classId, cls.name AS name FROM mopClass AS cls WHERE cls.cid=$cmpId ORDER BY cls.ord";
}
$resClasses = $link->query($sql);

$results = array();

while ($rClasses = $resClasses->fetch_assoc()) {

  $cname = $rClasses['name'];
  $cls = $rClasses['classId'];


  $sql = "SELECT max(leg) AS nleg FROM mopTeamMember tm, mopTeam t WHERE tm.cid = '$cmpId' AND t.cid = '$cmpId' AND tm.id = t.id AND t.cls = $cls";
  $resTeams = $link->query($sql);
  $rTeams = $resTeams->fetch_assoc();

  if (!is_null($rTeams))
    $numlegs =  $rTeams['nleg'];


  if (is_null($rTeams) || is_null($numlegs)) {
    //No teams;        
    $sql = "SELECT cmp.id AS id, cmp.name AS name, org.name AS team,  org.nat AS nat, cmp.rt AS time, cmp.rt + cmp.st AS finish, cmp.stat AS status " .
      "FROM mopCompetitor cmp LEFT JOIN mopOrganization AS org ON cmp.org = org.id AND cmp.cid = org.cid " .
      "WHERE cmp.cls = '$cls' AND cmp.stat > 0 " .
      "AND cmp.cid = '$cmpId' ORDER BY cmp.stat, cmp.rt ASC, cmp.id";
    $rname = "Finish";
    $resResults = $link->query($sql);
    $classResult = calculateResult($resResults, $cname, 0, thresholdForClass($cname, $r['name']), runnersInClass($cls, $cmpId));
    $results = array_merge($results, $classResult);
  } else {
    die("only individual results with this endpoint!");

  }
}

function runnersInClass($cls, $cmpId){
  $link = ConnectToDB();
  $sql = "SELECT COUNT(id) AS num FROM mopCompetitor WHERE cls = '$cls' AND cid = '$cmpId'";

  $resResults = $link->query($sql);
  $allResults=$resResults->fetch_assoc();
  $sql = "SELECT COUNT(id) AS num FROM mopCompetitor WHERE cls = '$cls' AND cid = '$cmpId' AND stat > 0";

  $resResults = $link->query($sql);
  $finishedResults=$resResults->fetch_assoc();



  return "(".$finishedResults["num"]."/".$allResults["num"].")";

}

function thresholdForClass($clsname, $compname){
  if(strpos($compname, 'Quali') === FALSE) {
    return 0;
  }

  $categories = [
    "H35- A" => 8,
    "H45- A" => 9,
    "H55- A" => 9,
    "H65- A" => 10,
    "H75- A" => 100,
    "D35- A" => 6,
    "D45- A" => 11,
    "D55- A" => 10,
    "D65- A" => 100,
    "D75- A" => 100,
    "W21- A" => 14,
    "M21- A" => 7,
    "D-14 A" => 5,
    "D-16 A" => 5,
    "D-18 A" => 6,
    "H-14 A" => 6,
    "H-16 A" => 6,
    "H-18 A" => 100,
    "H-12" => 100,
    "D-12" => 100,
    "H-10" => 100,
    "D-10" => 100,
    "DirMS" => 100,
    "DirKL" => 100,
    "W21- B" => 14,
    "M21- B" => 7,
    "M21- C" => 7,
    "M21- D" => 7,
    "H35- B" => 8,
    "H45- B" => 9,
    "H45- C" => 9,
    "H55- B" => 9,
    "H55- C" => 9,
    "H65- B" => 10,
    "D35- B" => 6,
    "D45- B" => 11,
    "D55- B" => 10,
    "D-14 B" => 5,
    "D-16 B" => 5,
    "D-18 B" => 6,
    "H-14 B" => 6,
    "H-16 B" => 6
  ];
  return $categories[$clsname];
}

echo json_encode(
  array(
    'list'         =>  $results,
    'timestamp'    =>  time(),
    'time'    =>  date('H:i', time()),
    'eventconfig'  =>  $eventconfig,
    'clientconfig' =>  array(
      "columns" => 1,
      "paginate" => true,
      "displaytime" => 20
    ),
    'remote_ip' => $_SERVER['REMOTE_ADDR']
  )
);
