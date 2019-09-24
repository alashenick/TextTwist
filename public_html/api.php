<?php  
header('Access-Control-Allow-Origin: *');

session_start();
if (isset($_POST['reset'])){
//    session_reset();
//    removeCookie("PHPSESSID");
    $_SESSION["points"] = 0;
}

    function generate_rack($n){
      $tileBag = "AAAAAAAAABBCCDDDDEEEEEEEEEEEEFFGGGHHIIIIIIIIIJKLLLLMMNNNNNNOOOOOOOOPPQRRRRRRSSSSTTTTTTUUUUVVWWXYYZ";
      $rack_letters = substr(str_shuffle($tileBag), 0, $n);
      $temp = str_split($rack_letters);
      sort($temp);
      return implode($temp);
    };

    $dbhandle = new PDO("sqlite:scrabble.sqlite") or die("Failed to open DB");
    if (!$dbhandle) die ($error);
    if(isset($_POST['user-rack'])) {
        $myrack=$_POST['user-rack'];
    }else {
        $myrack = generate_rack(7);
    }
    
    $racks = [];
    for($i = 0; $i < pow(2, strlen($myrack)); $i++){
        $ans = "";
        for($j = 0; $j < strlen($myrack); $j++){
            //if the jth digit of i is 1 then include letter
            if (($i >> $j) % 2) {
              $ans .= $myrack[$j];
            }
        }
        if (strlen($ans) > 1){
            $racks[] = $ans;    
        }
    }
    
    $racks = array_unique($racks);
    $numracks = sizeof($racks);
    $words = $weights = array();
    
    for ($i=0; $i<$numracks;$i++) {
        if (array_key_exists($i,$racks)) {
            $thisrack = $racks[$i];
            
            $query = "SELECT words,weight FROM racks WHERE rack='".$thisrack."'";
            
            $statement = $dbhandle->prepare($query);
            $statement->execute();
            $results = $statement->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($results) > 0){
                $str= $results[0]['words'];
                $this_words = (explode("@@",$str));
                $this_weight = $results[0]['weight'];
                
                for ($j=0; $j<count($this_words); $j++) {
                    array_push($words,$this_words[$j]);
                    array_push($weights,$this_weight);
                    $numLetters = strlen($this_words[$j]);
                }
            }
        }
    }
    $results_array = array_combine($words,$weights);
    arsort($results_array);
    
    $valid_guess = "";
    $this_guess = "";
    $points = 0;
    
    if(!empty($_POST['user-guesses'])) {
        $this_guess = $_POST['user-guesses'];
        for ($i = 0; $i< sizeof($words); $i++) {
            if ($this_guess==$words[$i]) {
                $points = $weights[$i];
                $valid_guess = $this_guess;
           }
        }
    }
    if (!isset($_SESSION["points"])){
        $_SESSION["points"] = 0;
        $_SESSION["recent_points"] = 0;
    } else {
        $_SESSION["points"] += $points;
        $_SESSION["recent_points"] = $points;
    }

    header('HTTP/1.1 200 OK');
    header('Content-Type: application/json');
    echo json_encode(["rack"=>$myrack,"results"=>$results_array, "guess"=>$valid_guess, "total_points"=>$_SESSION["points"], "this_points"=>$_SESSION["recent_points"]]);
?>
