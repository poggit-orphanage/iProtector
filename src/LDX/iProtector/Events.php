<?php

<?php

declare(strict_types = 1);

namespace LDX\iProtector;

use pocketmine\level\Level;
use pocketmine\math\Vector3;

class AreaEvent{

	/** @var string */
	private $eventname;
	/** @var bool[] */
	public $eventflags;
	/** @var string */
	private $areaname;
	/** @var Vector3 */
	private $eventpos;
	/** @var int */
	private $eventtime;
	/** @var string */
	private $eventcommand;
	/** @var int */
	private $eventpoints;



	public function __construct(string $eventname, array $eventflags, Vector3 $eventpos, string $areaname, int $eventtime, string $eventcommand, int $eventpoints, Main $plugin){
		$this->eventname = strtolower($eventname);
		$this->eventflags = $eventflags;
		$this->eventpos = $eventpos;
		$this->areaname = $areaname;
		$this->eventtime = $eventtime;
		$this->eventcommand = $eventcommand;
		$this->eventpoints = $eventpoints;
		$this->plugin = $plugin;
		$this->save();
	}

}


/*
event flag // event on/off
event name // event id
event area // event area
event pos  // event start position inside area
event time // event trigger time in seconds/ticks after area enter
event command // command / functions triggered
event points // points given to player if survived & leaving area
*/
?>
