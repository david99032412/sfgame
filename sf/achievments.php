<?php

// Achievments by Greg

/*
	- List (Hungarian) -
	
	038 - A boldog 18-as
	001 - Világjáró
	017 - Sárkánylovas
	042 - Naturista
	012 - Lehúzók lehúzója
*/

class Achievments{
	public $data = [];
	
	function __construct($data){
		$this->data = $data;
		
		if($this->complete())
			$this->data = "1";
		else{
			$this->data = explode("/", $this->data);
			
			if(count($this->data) != 49)
				$this->generate();
		}
	}
	
	public function complete(){
		if(strlen($this->data) == 1 && $this->data == "1")
			return true;
		
		return false;
	}
	
	public function required($key){
		return -1;
	}
	
	public function generate(){
		for($i = 0; $i < 50; $i++){
			$req = $this->required($i);
			
			if($req < 0)
				$this->data[$i] = "n";
			else
				$this->data[$i] = $req;
		}
	}
	
	public function save($key, $val){
		if($this->complete())
			return "1";
		
		if($val === true)
			$this->data[$key] = "y";
		else{
			if($this->required($key) >= $val)
				$this->data[$key] = "y";
			else
				$this->data[$key] = $val;
		}
		
		return implode("/", $this->data);
	}
	
	public function getText(){
		for($i = 0; $i < 100; $i++)
			$achi[] = 0;
		$achi[] = "";
		
		for($i = 0; $i < 50; $i++){
			$temp = $this->data[$i];
			
			if($temp == "y")
				$achi[$i] = 1;
			else if(intval($temp) > 0)
				$achi[$i + 50] = $temp;
		}
		
		return $achi;
	}
}