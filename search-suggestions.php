<?php
include('config/constants.php');

header('Content-Type: application/json');

if(isset($_GET['q']) && !empty($_GET['q'])){
    $search_term = $conn->real_escape_string($_GET['q']);
    
    $sql = "SELECT DISTINCT title FROM tbl_food 
            WHERE title LIKE '%$search_term%' 
            AND active = 'Yes'
            LIMIT 5";
    
    $res = $conn->query($sql);
    
    $suggestions = array();
    
    if($res && $res->num_rows > 0){
        while($row = $res->fetch_assoc()){
            $suggestions[] = $row['title'];
        }
    }
    
    echo json_encode($suggestions);
}
else{
    echo json_encode(array());
}
?>

