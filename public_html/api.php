<?php  
header('Access-Control-Allow-Origin: *');



    function generate_rack($n){
      $tileBag = "AAAAAAAAABBCCDDDDEEEEEEEEEEEEFFGGGHHIIIIIIIIIJKLLLLMMNNNNNNOOOOOOOOPPQRRRRRRSSSSTTTTTTUUUUVVWWXYYZ";
      $rack_letters = substr(str_shuffle($tileBag), 0, $n);
      $temp = str_split($rack_letters);
      sort($temp);
      return implode($temp);
    };

    $dbhandle = new PDO("sqlite:scrabble.sqlite") or die("Failed to open DB");
    if (!$dbhandle) die ($error);
    if(($_POST)) {
        $myrack=$_POST['user-rack'];
        //mysql_query("SQL insert statement.......");
        }else {
            $myrack = generate_rack(7);
        }
    //$myrack = generate_rack(7);
    //echo "Random rack: ".$myrack."\n\n";
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
    //echo "All sub-racks: ".json_encode(array_values($racks))."\n\n";
    $numracks = sizeof($racks);
    $words = $bingos = $weights = array();
    
    for ($i=0; $i<$numracks;$i++) {
        if (array_key_exists($i,$racks)) {
            $thisrack = $racks[$i];
            
            $query = "SELECT words,weight FROM racks WHERE rack='".$thisrack."'";
            
            $statement = $dbhandle->prepare($query);
            $statement->execute();
            $results = $statement->fetchAll(PDO::FETCH_ASSOC);
            //print_r($results);
            
            if (count($results) > 0){
                $str= $results[0]['words'];
                $this_words = (explode("@@",$str));
                $this_weight = $results[0]['weight'];
                
                for ($j=0; $j<count($this_words); $j++) {
                    array_push($words,$this_words[$j]);
                    array_push($weights,$this_weight);
                    $numLetters = strlen($this_words[$j]);
                    if ($numLetters==7) {
                        array_push($bingos,$this_words[$j]);
                    //} else if ($numLetters==6) {
                    //    array_push($sixLetterWords,$this_words[$j]);
                    //} else if ($numLetters==5) {
                    //    array_push($fiveLetterWords,$this_words[$j]);
                    //} else if ($numLetters==4) {
                    //    array_push($fourLetterWords,$this_words[$j]);
                    //} else if ($numLetters==3) {
                    //    array_push($threeLetterWords,$this_words[$j]);
                    //} else if ($numLetters==2) {
                    //    array_push($twoLetterWords,$this_words[$j]);
                    }
                }
            }
        }
    }
    $results_array = array_combine($words,$weights);
    arsort($results_array);
    //print_r($results_array);
    
    
    
    //echo "All words: ".sizeof($words)." total possible words exists.\n". json_encode($words)."\n\n";
    //echo "Bingos: ".sizeof($bingos)." bingos exist.\n".json_encode($bingos)."\n\n";
    //echo "Six letter words: ".sizeof($sixLetterWords)." six letter words exist.\n".json_encode($sixLetterWords)."\n\n";
    //echo "Five letter words: ".sizeof($fiveLetterWords)." five letter words exist.\n".json_encode($fiveLetterWords)."\n\n";
    //echo "Four letter words: ".sizeof($fourLetterWords)." four letter words exist.\n".json_encode($fourLetterWords)."\n\n";
    //echo "Three letter words: ".sizeof($threeLetterWords)." three letter words exist.\n".json_encode($threeLetterWords)."\n\n";
    //echo "Two letter words: ".sizeof($twoLetterWords)." two letter words exist.\n".json_encode($twoLetterWords)."\n\n";
    


    header('HTTP/1.1 200 OK');
    header('Content-Type: application/json');
    echo json_encode(["rack"=>$myrack,"results"=>$results_array]);
?>
