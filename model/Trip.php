<?php
require_once 'model/Db.php';
require_once 'model/Guest.php';

class Trip {

  static function listTrips() {
    $db = Db::getDbObject();
    $query = "SELECT t.tripid, t.direction, d.drivername, c.carname, t.timestart, t.timearrival, 
            (strftime('%s', timearrival) - strftime('%s', timestart) ) AS triptime
            FROM trip t 
            JOIN cars c on t.carid = c.carid
            JOIN drivers d on t.driverid = d.driverid
            ;
            ";
    // Save Result as array (because connection will be closed afterwards!)
    $resultArray = $db->query($query)->fetchAll();
    $db = null; // close connection
    return $resultArray;
  }

  private static function listTripsWithEmptyPickupSeats() {
    $db = Db::getDbObject();
    $query = "SELECT t.tripid, t.direction, d.drivername, c.carname, t.timestart, t.timearrival,
          (COUNT(DISTINCT p.guestid) - c.carpassengers ) AS emptyseats,
          (strftime('%s', timearrival) - strftime('%s', timestart) ) AS triptime
          FROM trip t
              JOIN pickup p on t.tripid = p.tripid
              JOIN cars c on t.carid = c.carid
              JOIN drivers d on t.driverid = d.driverid
          GROUP BY t.tripid
          HAVING emptyseats > 0
          ;
        ";
    // Save Result as array (because connection will be closed afterwards!)
    $resultArray = $db->query($query)->fetchAll();
    $db = null; // close connection
    return $resultArray;
  }

  private static function listTripsWithEmptyDropoffSeats() {
    $db = Db::getDbObject();
    $query = "SELECT t.tripid, t.direction, d.drivername, c.carname, t.timestart, t.timearrival,
       (strftime('%s', timearrival) - strftime('%s', timestart) ) AS triptime
          FROM trip t
             JOIN dropoff d2 on t.tripid = d2.tripid
             JOIN cars c on t.carid = c.carid
             JOIN drivers d on t.driverid = d.driverid
          GROUP BY t.tripid
          HAVING COUNT(DISTINCT d2.guestid) < c.carpassengers
          ;
        ";
    // Save Result as array (because connection will be closed afterwards!)
    $resultArray = $db->query($query)->fetchAll();
    $db = null; // close connection
    return $resultArray;
  }

  static function testTrips(){
    // Get open dropoffs
    $openDropoffs = Guest::listGuestsForDropOff();

    // Get open pickups
    $openPickups = Guest::listGuestsForPickUp();

    // Get already planned trips
    $dropoffTrips = self::listTripsWithEmptyDropoffSeats();
    $pickupTrips = self::listTripsWithEmptyPickupSeats();

    // -- place available --> Autobook
    // -- no place available
    //    -- -- no further cars --> NOT POSSIBLE
    //    -- -- no further drivers --> NOT POSSIBLE
    //    -- -- car and driver available --> Zuweisung möglich

    // Check already planned trips for dropoff
    foreach ($openDropoffs as $dropoff){
      if(empty($dropoff['date'])) continue;

      // Check dropoff dates
      foreach ($dropoffTrips as $trip){
        if($trip['direction']=='Hotel->Airport' && $trip['emptyseats']>0){
          $timediff = strtotime( $dropoff['date']) - strtotime($trip['timearrival']);
          if($timediff > 0 && $timediff < strtotime('00:30')){

            if(Trip::bookDropOff($dropoff['guestid'], $trip['tripid'])){
              $trip['emptyseats']--;
            }else{
              // Did not book - error
            }
            if(count(Car::checkAvailCars($dropoff['date'])) == 0){
              // no cars available
              echo "<h2 style='color: red'>No cars available!</h2>";
            }
            if(count(Driver::checkAvailDriver($dropoff['date'])) == 0){
              // no cars available
              echo "<h2 style='color: red'>No Driver available!</h2>";
            }
          }
        }
      }
    }

    // Check already planned trips for pickup
    foreach ($openPickups as $pickup){
      if(empty($pickup['date'])) continue;

      // Check pickup dates
      foreach ($pickupTrips as $trip){
        if($trip['direction']=='Airport->Hotel' && $trip['emptyseats']>0){
          $timediff = strtotime( pic['date']) - strtotime($trip['timearrival']);
          if($timediff > 0 && $timediff < strtotime('00:30')){

            if(Trip::bookPickUp($pickup['guestid'], $trip['tripid'])){
              $trip['emptyseats']--;
            }else{
              // Did not book - error
            }
            if(count(Car::checkAvailCars($pickup['date'])) == 0){
              // no cars available
              echo "<h2 style='color: red'>No cars available!</h2>";
            }
            if(count(Driver::checkAvailDriver($pickup['date'])) == 0){
              // no cars available
              echo "<h2 style='color: red'>No Driver available!</h2>";
            }
          }
        }
      }
    }



  }

  private static function bookDropOff($guestid, $tripid) {
    $db = Db::getDbObject();
    $statement = $db->prepare('UPDATE dropoff SET tripid=:tripid  WHERE guestid=:guestid;');
    $statement->bindParam(':guestid', $guestid);
    $statement->bindParam(':tripid', $tripid);
    $result = $statement->execute();
    $db = null;
    return $result;
  }

  private static function bookPickUp($guestid, $tripid) {
    $db = Db::getDbObject();
    $statement = $db->prepare('UPDATE pickup SET tripid=:tripid  WHERE guestid=:guestid;');
    $statement->bindParam(':guestid', $guestid);
    $statement->bindParam(':tripid', $tripid);
    $result = $statement->execute();
    $db = null;
    return $result;
  }

}