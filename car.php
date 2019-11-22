<?php
require_once 'Db.php';

function addCar($name, $seats) {
  $db = Db::getDbObject();
  $statement = $db->prepare("INSERT INTO shuttle (name, airport, seats) VALUES (:name, :airport, :seats)");
  $statement->bindParam(':name', $name);
  $statement->bindValue(':airport', 1);
  $statement->bindParam(':seats', $seats, PDO::PARAM_INT);
  $statement->execute();

  $db = null;
  return listCars();
}

function listCars() {
  $db = Db::getDbObject();
  $query = 'SELECT * FROM shuttle';
  // $result = $db->query($query);
  // Save Result as array (because connection will be closed afterwards!)
  $resultArray = $db->query($query)->fetchAll();
  $db = null; // close connection
  //return $result->fetchAll();
  return $resultArray;
}

function deleteCar($carId) {
  $db = Db::getDbObject();
  $statement = $db->prepare('DELETE FROM shuttle WHERE id = :carId');
  $statement->bindParam(':carId', $carId, PDO::PARAM_INT);
  $statement->execute();
  $db = null;
  return listCars();
}

if ($_POST) {
  if ($_POST['method'] == "DELETE") {
    // DELETE driver
    if (empty($_POST['carId'])) die("No CarId given");
    deleteCar($_POST['carId']);
  } else {
    // New Driver
    if (empty($_POST['carName'])) die("No carName given!");
    if (empty($_POST['seats'])) die("No seats given!");
    addCar($_POST['carName'], $_POST['seats']);
  }
}


?>

<html>
<body>

<a href="index.html">Back to main page</a>

<!-- -->
<hr />
<table>
    <thead>
    <tr>
        <th>Car ID</th>
        <th>Car Name</th>
        <th>Number Seats</th>
        <th><i>Delete</i></th>
    </tr>
    </thead>
    <tbody>
    <?php
    foreach (listCars() as $row) {
      ?>
        <tr>
            <td><?= $row['id'] ?></td>
            <td><?= $row['name'] ?></td>
            <td><?= $row['seats'] ?></td>
            <td>
                <form action="car.php" method="post">
                    <input type="hidden" name="method" value="DELETE">
                    <input type="hidden" name="carId" value="<?= $row['id'] ?>">
                    <button type="submit">Delete</button>
                </form>
            </td>
        </tr>
      <?php
    }
    ?>
    </tbody>
</table>

<!-- -->
<hr/>
<form action="car.php" method="post">
    <h3>Add Driver</h3>
    <label for="carName">Name of Car</label>
    <input type="text" name="carName">
    <label for="seats">Number of Seats</label>
    <input type="number" name="seats">
    <input type="submit">
</form>
<!-- -->


</body>
</html>
