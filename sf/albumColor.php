<?php

class Album{

	//base64 data
	public $data;

	//boolean array of items, use getItemIndex
	private $contents;

	//number of items in album, instead of counting every time, i guess it's better
	public $count;

	public function Album($data, $count){
		$this->data = $data;
		$this->count = $count;

		$this->decode();
	}

	private function decode(){
		$decoded = base64_decode(str_pad(strtr($this->data, '-_', '+/'), strlen($this->data) % 4, '=', STR_PAD_RIGHT));
		$byteArray = unpack('C*', $decoded);

		$this->contents = [];

		foreach($byteArray as $byte){
			$this->contents[] = ((($byte & 128) / 128) == 1);
			$this->contents[] = ((($byte & 64) / 64) == 1);
			$this->contents[] = ((($byte & 32) / 32) == 1);
			$this->contents[] = ((($byte & 16) / 16) == 1);
			$this->contents[] = ((($byte & 8) / 8) == 1);
			$this->contents[] = ((($byte & 4) / 4) == 1);
			$this->contents[] = ((($byte & 2) / 2) == 1);
			$this->contents[] = (($byte & 1) == 1);
		}
	}

	public function encode(){
		$s = "";
		$final = "";

		foreach($this->contents as $bool){
			$s .= $bool ? "1" : "0";
			if(strlen($s) == 8){
				$final .= pack('C*', bindec($s));
				$s = "";
			}
		}

		$this->data = strtr(base64_encode($final), '+/', '-_');
	}

	public function hasItem($item){
		return $this->contents[$this->getItemIndex($item)];
	}

	public function addItem($item){

		//if item is not an equipable, can be passed on using album, it's passing every item in possesion
		if($item->type > 10)
			return false;

		$index = Album::getItemIndex($item);

		if($this->contents[$index])
			return false;


		$this->contents[$index] = true;
		$this->count++;

		//if not epic or relic, add all colors
		// if(!$item->isEpic && $item->type != 10){
		// 	$this->count += 4;
		// 	for($i = 1; $i <= 4; $i++){
		// 		$this->contents[$index + $i] = true;
		// 	}
		// }

		return true;
	}

	//arg - array of items
	public function addItems($items){
		$bool = false;

		foreach($items as $item){
			if($this->addItem($item))
				$bool = true;
		}

		return $bool;
	}

	public function hasMonster($id){
		return $this->contents[abs($id) - 1];
	}

	public function addMonster($id){
		$id = abs($id) - 1;
		if(!$this->contents[$id]){
			$this->contents[$id] = true;
			$this->count++;
			return true;
		}

		return false;
	}

	//color check removed at the bottom, returns first color index
	//add item at index and 4 after if not epic && not relic
	private static function getItemIndex($item){

		$type = $item->type;
		$id = $item->id;
		$forclass = $item->forClass;
		$isEpic = $item->isEpic;

		$index = 0;

		// $CLASS_WARRIOR = 1;
		// $CLASS_MAGE = 2;
		// $CLASS_ROUGE = 3;

		// accesory offset start 800
		if ($forclass == 0) {
			switch ($type) {
			case 8:
				if ($isEpic)
					$index = 1010;
				else
					$index = 800;
				break;
			case 9:
				if ($isEpic)
					$index = 1210;
				else
					$index = 1050;
				break;
			case 10:
				if ($isEpic)
					$index = 1324;
				else
					$index = 1250;
				break;
			}
		} else if ($forclass == 1) {
			switch ($type) {
			case 1:
				$index = 1364;
				break;
			case 2:
				$index = 1704;
				break;
			case 3:
				$index = 1844;
				break;
			case 4:
				$index = 1984;
				break;
			case 5:
				$index = 2124;
				break;
			case 6:
				$index = 2264;
				break;
			case 7:
				$index = 2404;
				break;
			}
		} else {
			switch ($type) {
			case 1:
				$index = 2544;
				break;
			case 3:
				$index = 2684;
				break;
			case 4:
				$index = 2824;
				break;
			case 5:
				$index = 2964;
				break;
			case 6:
				$index = 3104;
				break;
			case 7:
				$index = 3244;
				break;
			}
		}
		
		// if epic && not accessory - offset +100 | accessory offset in switch
		if ($isEpic && $forclass > 0) {
			$index += 100;
			// special case for warrior weapons, 200
			if ($forclass == 1 && $type == 1) {
				$index += 200;
			}
		}

		// rouge offset, same items (ammount) as mage -> same intervals
		if ($forclass == 3)
			$index += 840;

		if ($isEpic) {
			$index += $id - 50;
		} else if($type<10){
			$index += (($id - 1) * 5) + $item->getColor();
		}else{ //relics have 1 color
			$index += ($id-1);
		}

		return $index;
	}

	public static function getDefaultData(){
		return "AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA==";
	}
}


?>