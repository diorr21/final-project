<?php
session_start();

$DB_HOST = getenv("DB_HOST") ? getenv("DB_HOST") : "localhost";
$DB_USER = getenv("DB_USER") ? getenv("DB_USER") : "root";
$DB_PASS = getenv("DB_PASS") ? getenv("DB_PASS") : "";
$DB_NAME = getenv("DB_NAME") ? getenv("DB_NAME") : "grading_system";
$DB_PORT = getenv("DB_PORT") ? (int)getenv("DB_PORT") : 3307;

$conn = mysqli_connect($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME, $DB_PORT);s
if(!$conn){
  http_response_code(500);
  header("Content-Type: application/json");
  echo json_encode(["ok"=>false,"error"=>"db_connect_failed"]);
  exit();
}

header("Content-Type: application/json");

$action = isset($_GET["action"]) ? $_GET["action"] : "";

function out($arr){
  echo json_encode($arr);
  exit();
}

function pickScore($dev, $acc){
  $dev = trim((string)$dev);
  $acc = trim((string)$acc);
  if($dev !== "" && $acc === "") return (int)$dev;
  if($acc !== "" && $dev === "") return (int)$acc;
  if($dev !== "" && $acc !== "") return (int)$dev;
  return 0;
}

if($action === "logout"){
  session_destroy();
  out(["ok"=>true]);
}

if($action === "session"){
  if(isset($_SESSION["user"])){
    out(["ok"=>true,"user"=>$_SESSION["user"],"role"=>$_SESSION["role"]]);
  }
  out(["ok"=>false]);
}

if($action === "login"){
  $u = isset($_POST["username"]) ? $_POST["username"] : "";
  $p = isset($_POST["password"]) ? $_POST["password"] : "";
  $u = mysqli_real_escape_string($conn, $u);
  $p = mysqli_real_escape_string($conn, $p);

  $sql = "SELECT username, role FROM judges WHERE username='$u' AND password='$p' LIMIT 1";
  $res = mysqli_query($conn, $sql);
  if($res && mysqli_num_rows($res) === 1){
    $row = mysqli_fetch_assoc($res);
    $_SESSION["user"] = $row["username"];
    $_SESSION["role"] = $row["role"];
    out(["ok"=>true,"user"=>$row["username"],"role"=>$row["role"]]);
  }
  out(["ok"=>false,"error"=>"invalid_login"]);
}

if($action === "submit"){
  if(!isset($_SESSION["user"]) || $_SESSION["role"] !== "judge"){
    out(["ok"=>false,"error"=>"not_logged_in"]);
  }

  $group_number = isset($_POST["group_number"]) ? trim($_POST["group_number"]) : "";
  $group_members = isset($_POST["group_members"]) ? trim($_POST["group_members"]) : "";
  $project_title = isset($_POST["project_title"]) ? trim($_POST["project_title"]) : "";

  if($group_number === ""){
    out(["ok"=>false,"error"=>"group_required"]);
  }
  if($group_members === ""){
    out(["ok"=>false,"error"=>"members_required"]);
  }
  if($project_title === ""){
    out(["ok"=>false,"error"=>"title_required"]);
  }

  $judge = $_SESSION["user"];
  $comments = isset($_POST["comments"]) ? trim($_POST["comments"]) : "";

  $s1 = pickScore($_POST["d1"] ?? "", $_POST["a1"] ?? "");
  $s2 = pickScore($_POST["d2"] ?? "", $_POST["a2"] ?? "");
  $s3 = pickScore($_POST["d3"] ?? "", $_POST["a3"] ?? "");
  $s4 = pickScore($_POST["d4"] ?? "", $_POST["a4"] ?? "");

  $total = $s1 + $s2 + $s3 + $s4;

  $group_safe = mysqli_real_escape_string($conn, $group_number);
  $members_safe = mysqli_real_escape_string($conn, $group_members);
  $title_safe = mysqli_real_escape_string($conn, $project_title);
  $judge_safe = mysqli_real_escape_string($conn, $judge);
  $comments_safe = mysqli_real_escape_string($conn, $comments);

  $sql = "INSERT INTO grades (group_number, group_members, project_title, judge_name, total, comments)
          VALUES ('$group_safe', '$members_safe', '$title_safe', '$judge_safe', $total, '$comments_safe')";
  mysqli_query($conn, $sql);

  $sql2 = "SELECT AVG(total) AS avg_total FROM grades WHERE group_number='$group_safe'";
  $res2 = mysqli_query($conn, $sql2);
  $avg = 0;
  if($res2){
    $row2 = mysqli_fetch_assoc($res2);
    $avg = (float)$row2["avg_total"];
  }

  out(["ok"=>true,"judge_total"=>$total,"group_average"=>round($avg,2)]);
}

if($action === "admin"){
  if(!isset($_SESSION["user"]) || $_SESSION["role"] !== "admin"){
    out(["ok"=>false,"error"=>"not_admin"]);
  }

  $rows = [];
  $sql = "SELECT group_number,
                 MAX(group_members) AS group_members,
                 MAX(project_title) AS project_title,
                 AVG(total) AS avg_total,
                 COUNT(*) AS submissions
          FROM grades
          GROUP BY group_number
          ORDER BY group_number";
  $res = mysqli_query($conn, $sql);
  if($res){
    while($r = mysqli_fetch_assoc($res)){
      $rows[] = [
        "group_number" => $r["group_number"],
        "group_members" => $r["group_members"],
        "project_title" => $r["project_title"],
        "avg_total" => round((float)$r["avg_total"], 2),
        "submissions" => (int)$r["submissions"]
      ];
    }
  }
  out(["ok"=>true,"rows"=>$rows]);
}

out(["ok"=>false,"error"=>"bad_action"]);