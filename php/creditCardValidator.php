<?php

//Używając Algorytmu Luhna
function creditCardValidator($cardNumber) {
  if(strlen($cardNumber) !== 16 || !ctype_digit($cardNumber)) return false;
  
  $numbers = str_split($cardNumber, 1);
  $sum = 0;
  
  for($i = 0; $i < 16; $i++)
  {
  	if($i % 2 === 0) $numbers[$i] *=2;
    if($numbers[$i] > 9)
    {
      $twoNumbers = str_split($numbers[$i], 1);
      $numbers[$i] = $twoNumbers[0] + $twoNumbers[1];
    }
    $sum += $numbers[$i];
  }
   
  return ($sum % 10 === 0);
}

