<?php

declare(strict_types = 1);

namespace LDX\iProtector;

use pocketmine\entity\Entity;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\level\Level;
use pocketmine\scheduler\PluginTask;

class Events {

	/** @var string */
	public $eventname;
	/** @var bool[] */
	public $eventflags;
	/** @var string */
	public $areaname;
	/** @var int */
	public $eventtime;
	/** @var string */
	public $eventcommand;
	/** @var int */
	public $eventpoints;



	public function __construct(string $eventname, array $eventflags, string $areaname, int $eventtime, array $eventcommand, int $eventpoints, Main $plugin){
		$this->eventname = strtolower($eventname);
		$this->eventflags = $eventflags;
		$this->areaname = strtolower($areaname);
		$this->eventtime = $eventtime;
		$this->eventcommand = $eventcommand;
		$this->eventpoints = $eventpoints;
		$this->plugin = $plugin;
		$this->save();
	}


	public function delete() : void{
		unset($this->plugin->events[$this->getName()]);
		$this->plugin->saveEvents();
	}

	public function save() : void{
		$this->plugin->events[$this->eventname] = $this;
	}
}


/*
event flag // event on/off
event name // event id
event area // event area
event time // event trigger time in seconds/ticks after area enter
event command // command / functions triggered
event points // points given to player if survived & leaving area
*/
