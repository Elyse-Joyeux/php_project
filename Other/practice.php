<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>factors</title>
</head>
<body>
    <div class="input">
        <form action="practice.php" method="post">
            <label for="number">Enter a number:</label>
            <input type="number" id="number" name="number">
            <input type="submit" name = "submit" value="Find Factors">
        </form>
    </div>
    <?php
    function findFactors(){
        if ($_SERVER['REQUEST_METHOD']==='POST'){
            $nbr = $_POST['number'];
            $submit = $_POST['submit'];
            
            for($i = 1; $i<=$nbr; $i++){
                if($nbr % $i == 0){
                    echo $i. " ";
                }
            }
            
        }
    }
    findFactors();
    ?>
</body>
</html>
