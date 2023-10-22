<?php

/**
 * Gets a start date, without setting it ealier than earliest date available 
 * @param string $minDate in format yyyy-mm-dd   
 * @param string $endDate in format yyyy-mm-dd 
 * optional @param string $subtractDays 
 * @return string in format yyyy-mm-dd          
 */
function setStartDate($minDate, $endDate, $subtractDays='-28 days') {
    $minDateObj = new DateTime($minDate);  
    $endDateObj = new DateTime($endDate);
    $startDateObj = $endDateObj->modify($subtractDays);
    $startDate = ($startDateObj < $minDateObj) 
        ? $minDate 
        : $startDateObj->format('Y-m-d');
    return $startDate;    
}

/**
 * Creates a prepared statement, binds variables, and queries database 
 * @param object $conn from src/conn.php  
 * @param string $sql from src/sql.php 
 * @param string $type from src/sql.php 
 * @param array $varsArray to be unpacked and bound to prepared statement
 * @return array           
 */
function get_data($conn, $sql, $types, $varsArray) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$varsArray);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];
    while ($row = $result->fetch_assoc()) $data[] = $row;
    $stmt->close();
    return $data;
}
