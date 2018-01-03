<?php

declare(strict_types = 1);

namespace LDX\iProtector;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\entity\Entity;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;
use pocketmine\Server;
// edit genboy
use pocketmine\event\player\PlayerMoveEvent;

class Main extends PluginBase implements Listener{

	/** @var array */
	private $levels = [];
	/** @var Area[] */
	public $areas = [];

	/** @var bool */
	private $god = false;
	/** @var bool */
	private $edit = false;
	/** @var bool */
	private $touch = false;

	/** @var bool[] */
	private $selectingFirst = [];
	/** @var bool[] */
	private $selectingSecond = [];

	/** @var Vector3[] */
	private $firstPosition = [];
	/** @var Vector3[] */
	private $secondPosition = [];

	/** @var string (genboy fork) */
	private $inArea = '';

	/** @var string (genboy fork) */
	private $lastArea = '';


	// + textArea[]  Genboy edit
	public function onEnable() : void{

		// fork notice genboy
		$this->getLogger()->info(TextFormat::GREEN . "iProtector by poggit-orphanage/Genboy fork enabled!");

		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		if(!is_dir($this->getDataFolder())){
			mkdir($this->getDataFolder());
		}
		if(!file_exists($this->getDataFolder() . "areas.json")){
			file_put_contents($this->getDataFolder() . "areas.json", "[]");
		}
		if(!file_exists($this->getDataFolder() . "config.yml")){
			$c = $this->getResource("config.yml");
			$o = stream_get_contents($c);
			fclose($c);
			file_put_contents($this->getDataFolder() . "config.yml", str_replace("DEFAULT", $this->getServer()->getDefaultLevel()->getName(), $o));
		}
		$data = json_decode(file_get_contents($this->getDataFolder() . "areas.json"), true);
		foreach($data as $datum){
			// + textArea[]  Genboy edit
			new Area($datum["name"], $datum["flags"], new Vector3($datum["pos1"]["0"], $datum["pos1"]["1"], $datum["pos1"]["2"]), new Vector3($datum["pos2"]["0"], $datum["pos2"]["1"], $datum["pos2"]["2"]), $datum["level"], $datum["whitelist"], $datum["areatext"], $this);
		}
		$c = yaml_parse_file($this->getDataFolder() . "config.yml");

		$this->god = $c["Default"]["God"];
		$this->edit = $c["Default"]["Edit"];
		$this->touch = $c["Default"]["Touch"];

		foreach($c["Worlds"] as $level => $flags){
			$this->levels[$level] = $flags;
		}
	}

	public function onCommand(CommandSender $sender, Command $cmd, string $label, array $args) : bool{
		if(!($sender instanceof Player)){
			$sender->sendMessage(TextFormat::RED . "Command must be used in-game.");

			return true;
		}
		if(!isset($args[0])){
			return false;
		}
		$playerName = strtolower($sender->getName());
		$action = strtolower($args[0]);
		$o = "";

		switch($action){
			case "pos1":
				if($sender->hasPermission("iprotector") || $sender->hasPermission("iprotector.command") || $sender->hasPermission("iprotector.command.area") || $sender->hasPermission("iprotector.command.area.pos1")){
					if(isset($this->selectingFirst[$playerName]) || isset($this->selectingSecond[$playerName])){
						$o = TextFormat::RED . "You're already selecting a position!";
					}else{
						$this->selectingFirst[$playerName] = true;
						$o = TextFormat::GREEN . "Please place or break the first position.";
					}
				}else{
					$o = TextFormat::RED . "You do not have permission to use this subcommand.";
				}
				break;
			case "pos2":
				if($sender->hasPermission("iprotector") || $sender->hasPermission("iprotector.command") || $sender->hasPermission("iprotector.command.area") || $sender->hasPermission("iprotector.command.area.pos2")){
					if(isset($this->selectingFirst[$playerName]) || isset($this->selectingSecond[$playerName])){
						$o = TextFormat::RED . "You're already selecting a position!";
					}else{
						$this->selectingSecond[$playerName] = true;
						$o = TextFormat::GREEN . "Please place or break the second position.";
					}
				}else{
					$o = TextFormat::RED . "You do not have permission to use this subcommand.";
				}
				break;
			case "create":
				if($sender->hasPermission("iprotector") || $sender->hasPermission("iprotector.command") || $sender->hasPermission("iprotector.command.area") || $sender->hasPermission("iprotector.command.area.create")){
					if(isset($args[1])){
						if(isset($this->firstPosition[$playerName], $this->secondPosition[$playerName])){
							if(!isset($this->areas[strtolower($args[1])])){
								new Area(strtolower($args[1]), ["edit" => true, "god" => false, "touch" => true], $this->firstPosition[$playerName], $this->secondPosition[$playerName], $sender->getLevel()->getName(), [$playerName], array(), $this);
								$this->saveAreas();
								unset($this->firstPosition[$playerName], $this->secondPosition[$playerName]);
								$o = TextFormat::AQUA . "Area created!";
							}else{
								$o = TextFormat::RED . "An area with that name already exists.";
							}
						}else{
							$o = TextFormat::RED . "Please select both positions first.";
						}
					}else{
						$o = TextFormat::RED . "Please specify a name for this area.";
					}
				}else{
					$o = TextFormat::RED . "You do not have permission to use this subcommand.";
				}
				break;
			case "list":
				if($sender->hasPermission("iprotector") || $sender->hasPermission("iprotector.command") || $sender->hasPermission("iprotector.command.area") || $sender->hasPermission("iprotector.command.area.list")){
					$o = TextFormat::AQUA . "Areas: " . TextFormat::RESET;
					$i = 0;
					foreach($this->areas as $area){
						if($area->isWhitelisted($playerName)){
							$o .= $area->getName() . " (" . implode(", ", $area->getWhitelist()) . "), ";
							$i++;
						}
					}
					if($i === 0){
						$o = "There are no areas that you can edit";
					}
				}
				break;
			case "here":
				if($sender->hasPermission("iprotector") || $sender->hasPermission("iprotector.command") || $sender->hasPermission("iprotector.command.area") || $sender->hasPermission("iprotector.command.area.here")){
					$o = "";
					foreach($this->areas as $area){
						if($area->contains($sender->getPosition(), $sender->getLevel()->getName()) && $area->getWhitelist() !== null){
							$o .= TextFormat::AQUA . "Area " . $area->getName() . " can be edited by " . implode(", ", $area->getWhitelist());
							break;
						}
					}
					if($o === "") {
						$o = TextFormat::RED . "You are in an unknown area";
					}
				}
				break;
			case "tp":
				if (!isset($args[1])){
					$o = TextFormat::RED . "You must specify an existing Area name";
					break;
				}
				if($sender->hasPermission("iprotector") || $sender->hasPermission("iprotector.command") || $sender->hasPermission("iprotector.command.area") || $sender->hasPermission("iprotector.command.area.tp")){
					$area = $this->areas[strtolower($args[1])];
					if($area !== null && $area->isWhitelisted($playerName)){
						$levelName = $area->getLevelName();
						if(isset($levelName) && Server::getInstance()->loadLevel($levelName) != false){
							$o = TextFormat::GREEN . "You are teleporting to Area " . $args[1];
							$sender->teleport(new Position($area->getFirstPosition()->getX(), $area->getFirstPosition()->getY() + 0.5, $area->getFirstPosition()->getZ(), $area->getLevel()));
						}else{
							$o = TextFormat::RED . "The level " . $levelName . " for Area ". $args[1] ." cannot be found";
						}
					}else{
						$o = TextFormat::RED . "The Area " . $args[1] . " could not be found ";
					}
				}
				break;
			case "flag":
				if($sender->hasPermission("iprotector") || $sender->hasPermission("iprotector.command") || $sender->hasPermission("iprotector.command.area") || $sender->hasPermission("iprotector.command.area.flag")){
					if(isset($args[1])){
						if(isset($this->areas[strtolower($args[1])])){
							$area = $this->areas[strtolower($args[1])];
							if(isset($args[2])){
								if(isset($area->flags[strtolower($args[2])])){
									$flag = strtolower($args[2]);
									if(isset($args[3])){
										$mode = strtolower($args[3]);
										if($mode === "true" || $mode === "on"){
											$mode = true;
										}else{
											$mode = false;
										}
										$area->setFlag($flag, $mode);
									}else{
										$area->toggleFlag($flag);
									}
									if($area->getFlag($flag)){
										$status = "on";
									}else{
										$status = "off";
									}
									$o = TextFormat::GREEN . "Flag " . $flag . " set to " . $status . " for area " . $area->getName() . "!";
								}else{
									$o = TextFormat::RED . "Flag not found. (Flags: edit, god, touch)";
								}
							}else{
								$o = TextFormat::RED . "Please specify a flag. (Flags: edit, god, touch)";
							}
						}else{
							$o = TextFormat::RED . "Area doesn't exist.";
						}
					}else{
						$o = TextFormat::RED . "Please specify the area you would like to flag.";
					}
				}else{
					$o = TextFormat::RED . "You do not have permission to use this subcommand.";
				}
				break;
			case "delete":
				if($sender->hasPermission("iprotector") || $sender->hasPermission("iprotector.command") || $sender->hasPermission("iprotector.command.area") || $sender->hasPermission("iprotector.command.area.delete")){
					if(isset($args[1])){
						if(isset($this->areas[strtolower($args[1])])){
							$area = $this->areas[strtolower($args[1])];
							$area->delete();
							$o = TextFormat::GREEN . "Area deleted!";
						}else{
							$o = TextFormat::RED . "Area does not exist.";
						}
					}else{
						$o = TextFormat::RED . "Please specify an area to delete.";
					}
				}else{
					$o = TextFormat::RED . "You do not have permission to use this subcommand.";
				}
				break;
			case "whitelist":
				if($sender->hasPermission("iprotector") || $sender->hasPermission("iprotector.command") || $sender->hasPermission("iprotector.command.area") || $sender->hasPermission("iprotector.command.area.delete")){
					if(isset($args[1], $this->areas[strtolower($args[1])])){
						$area = $this->areas[strtolower($args[1])];
						if(isset($args[2])){
							$action = strtolower($args[2]);
							switch($action){
								case "add":
									$w = ($this->getServer()->getPlayer($args[3]) instanceof Player ? strtolower($this->getServer()->getPlayer($args[3])->getName()) : strtolower($args[3]));
									if(!$area->isWhitelisted($w)){
										$area->setWhitelisted($w);
										$o = TextFormat::GREEN . "Player $w has been whitelisted in area " . $area->getName() . ".";
									}else{
										$o = TextFormat::RED . "Player $w is already whitelisted in area " . $area->getName() . ".";
									}
									break;
								case "list":
									$o = TextFormat::AQUA . "Area " . $area->getName() . "'s whitelist:" . TextFormat::RESET;
									foreach($area->getWhitelist() as $w){
										$o .= " $w;";
									}
									break;
								case "delete":
								case "remove":
									$w = ($this->getServer()->getPlayer($args[3]) instanceof Player ? strtolower($this->getServer()->getPlayer($args[3])->getName()) : strtolower($args[3]));
									if($area->isWhitelisted($w)){
										$area->setWhitelisted($w, false);
										$o = TextFormat::GREEN . "Player $w has been unwhitelisted in area " . $area->getName() . ".";
									}else{
										$o = TextFormat::RED . "Player $w is already unwhitelisted in area " . $area->getName() . ".";
									}
									break;
								default:
									$o = TextFormat::RED . "Please specify a valid action. Usage: /area whitelist " . $area->getName() . " <add/list/remove> [player]";
									break;
							}
						}else{
							$o = TextFormat::RED . "Please specify an action. Usage: /area whitelist " . $area->getName() . " <add/list/remove> [player]";
						}
					}else{
						$o = TextFormat::RED . "Area doesn't exist. Usage: /area whitelist <area> <add/list/remove> [player]";
					}
				}else{
					$o = TextFormat::RED . "You do not have permission to use this subcommand.";
				}
				break;


			// + text  Genboy edit
			case "text":
				if($sender->hasPermission("iprotector") || $sender->hasPermission("iprotector.command") || $sender->hasPermission("iprotector.command.area") || $sender->hasPermission("iprotector.command.area.flag")){
					if(isset($args[1])){
						if(isset($this->areas[strtolower($args[1])])){
							$area = $this->areas[strtolower($args[1])];
							if(isset($args[2])){
									$field = strtolower($args[2]);
									// clean args string
									unset($args[0]);
									unset($args[1]);
									unset($args[2]);
									$text = implode(" ", $args);
									if( ( $field == 'enter' ||  $field == 'info' || $field == 'url' ) && $text !== "" ){
										if( trim( $text, " ") !== ""){
											$area->setAreaTextField($field, $text); // ( should validate last string also.. ! )
											$o = TextFormat::GREEN . "Updated ". $area->getName() ." textfield ". $field ." with '". $text ."'";
										}else{
											$area->setAreaTextField($field, 'Enter area');
											$o = TextFormat::RED . "Replaced ". $area->getName() ." textfield ". $field ." with default string.";
										}
									}else if( $field == 'list'){
										$o = TextFormat::AQUA . "Area " . $area->getName() . "'s text:" . TextFormat::RESET;
										foreach($area->getAreaText() as $f => $w){
											$o .= "\n$f: $w; ";
										}
									} // end field set
							}else{
								$o = TextFormat::RED . "Please specify a textfield. ( /area text <area> <enter/info/url> <textstring> )";
							}
						}else{
							$o = TextFormat::RED . "Area doesn't exist.";
						}
					}else{
						$o = TextFormat::RED . "Please specify the area you would like to add text to.";
					}
				}else{
					$o = TextFormat::RED . "You do not have permission to use this subcommand.";
				}
				break;
			// end + text Genboy edit


			default:
				return false;
		}
		$sender->sendMessage($o);

		return true;
	}

	public function saveAreas() : void{
		$areas = [];
		foreach($this->areas as $area){
			// + textArea[]  Genboy edit
			$areas[] = ["name" => $area->getName(), "flags" => $area->getFlags(), "pos1" => [$area->getFirstPosition()->getFloorX(), $area->getFirstPosition()->getFloorY(), $area->getFirstPosition()->getFloorZ()] , "pos2" => [$area->getSecondPosition()->getFloorX(), $area->getSecondPosition()->getFloorY(), $area->getSecondPosition()->getFloorZ()], "level" => $area->getLevelName(), "whitelist" => $area->getWhitelist(), "areatext" => $area->getAreaText()];
		}
		file_put_contents($this->getDataFolder() . "areas.json", json_encode($areas));
	}

	/**
	 * @param Entity $entity
	 *
	 * @return bool
	 */
	public function canGetHurt(Entity $entity) : bool{
		$o = true;
		$default = (isset($this->levels[$entity->getLevel()->getName()]) ? $this->levels[$entity->getLevel()->getName()]["God"] : $this->god);
		if($default){
			$o = false;
		}
		foreach($this->areas as $area){
			if($area->contains(new Vector3($entity->getX(), $entity->getY(), $entity->getZ()), $entity->getLevel()->getName())){
				if($default && !$area->getFlag("god")){
					$o = true;
					break;
				}
				if($area->getFlag("god")){
					$o = false;
				}
			}
		}

		return $o;
	}

	/**
	 * @param Player   $player
	 * @param Position $position
	 *
	 * @return bool
	 */
	public function canEdit(Player $player, Position $position) : bool{
		if($player->hasPermission("iprotector") || $player->hasPermission("iprotector.access")){
			return true;
		}
		$o = true;
		$g = (isset($this->levels[$position->getLevel()->getName()]) ? $this->levels[$position->getLevel()->getName()]["Edit"] : $this->edit);
		if($g){
			$o = false;
		}
		foreach($this->areas as $area){
			if($area->contains($position, $position->getLevel()->getName())){
				if($area->getFlag("edit")){
					$o = false;
				}
				if($area->isWhitelisted(strtolower($player->getName()))){
					$o = true;
					break;
				}
				if(!$area->getFlag("edit") && $g){
					$o = true;
					break;
				}
			}
		}

		return $o;
	}

	/**
	 * @param Player   $player
	 * @param Position $position
	 *
	 * @return bool
	 */
	public function canTouch(Player $player, Position $position) : bool{
		if($player->hasPermission("iprotector") || $player->hasPermission("iprotector.access")){
			return true;
		}
		$o = true;
		$default = (isset($this->levels[$position->getLevel()->getName()]) ? $this->levels[$position->getLevel()->getName()]["Touch"] : $this->touch);
		if($default){
			$o = false;
		}
		foreach($this->areas as $area){
			if($area->contains(new Vector3($position->getX(), $position->getY(), $position->getZ()), $position->getLevel()->getName())){
				if($area->getFlag("touch")){
					$o = false;
				}
				if($area->isWhitelisted(strtolower($player->getName()))){
					$o = true;
					break;
				}
				if(!$area->getFlag("touch") && $default){
					$o = true;
					break;
				}
			}
		}

		return $o;
	}

	public function onBlockTouch(PlayerInteractEvent $event) : void{
		$block = $event->getBlock();
		$player = $event->getPlayer();
		if(!$this->canTouch($player, $block)){
			$event->setCancelled();
		}
	}

	public function onBlockPlace(BlockPlaceEvent $event) : void{
		$block = $event->getBlock();
		$player = $event->getPlayer();
		$playerName = strtolower($player->getName());
		if(isset($this->selectingFirst[$playerName])){
			unset($this->selectingFirst[$playerName]);

			$this->firstPosition[$playerName] = $block->asVector3();
			$player->sendMessage(TextFormat::GREEN . "Position 1 set to: (" . $block->getX() . ", " . $block->getY() . ", " . $block->getZ() . ")");
			$event->setCancelled();
		}elseif(isset($this->selectingSecond[$playerName])){
			unset($this->selectingSecond[$playerName]);

			$this->secondPosition[$playerName] = $block->asVector3();
			$player->sendMessage(TextFormat::GREEN . "Position 2 set to: (" . $block->getX() . ", " . $block->getY() . ", " . $block->getZ() . ")");
			$event->setCancelled();
		}else{
			if(!$this->canEdit($player, $block)){
				$event->setCancelled();
			}
		}
	}


	/**
	 * @param BlockBreakEvent $event
	 * @ignoreCancelled true
	 */
	public function onBlockBreak(BlockBreakEvent $event) : void{
		$block = $event->getBlock();
		$player = $event->getPlayer();
		$playerName = strtolower($player->getName());
		if(isset($this->selectingFirst[$playerName])){
			unset($this->selectingFirst[$playerName]);

			$this->firstPosition[$playerName] = $block->asVector3();
			$player->sendMessage(TextFormat::GREEN . "Position 1 set to: (" . $block->getX() . ", " . $block->getY() . ", " . $block->getZ() . ")");
			$event->setCancelled();
		}elseif(isset($this->selectingSecond[$playerName])){
			unset($this->selectingSecond[$playerName]);

			$this->secondPosition[$playerName] = $block->asVector3();
			$player->sendMessage(TextFormat::GREEN . "Position 2 set to: (" . $block->getX() . ", " . $block->getY() . ", " . $block->getZ() . ")");
			$event->setCancelled();
		}else{
			if(!$this->canEdit($player, $block)){
				$event->setCancelled();
			}
		}
	}

	public function onHurt(EntityDamageEvent $event) : void{
		if($event->getEntity() instanceof Player){
			$player = $event->getEntity();
			if(!$this->canGetHurt($player)){
				$event->setCancelled();
			}
		}
	}





	/*
	 * start Genboy fork edit
	 * onMove event
	 *
	 */
	public function onMove(PlayerMoveEvent $ev)
    {

		$player = $ev->getPlayer(); // get player event
		$playerName = strtolower($player->getName());
		$playerarea = ''; // area check

		foreach($this->areas as $area){
			if( $this->isInside($area, $ev) ){
				$playerarea = $area;
			}
		}

			if( $playerarea != '' ){ // in Area..

				$this->inArea = $playerarea->getName();

				if( $this->lastArea != $this->inArea ){ // just entered

					$this->onEnterArea($area, $ev);

				}

			}else{

				// no area
				$this->inArea = '';

				// leaving Area
				if( $this->lastArea != '' ){

					$this->onLeaveArea($area, $ev);

				}

			}

 	}


	/*
	 * Enter area
	 * return @var obj area, event
	 */
	public function onEnterArea($area, $ev){

		$player = $ev->getPlayer();

		// leaving Area
		if( $this->lastArea != '' ){
			$player->sendMessage( TextFormat::RED ."Leaving area ". $this->lastArea );
		}
		$this->lastArea = $this->inArea;

		// Enter Area
		$msg = TextFormat::GREEN ."Enter area ". $this->inArea;

		if( !empty( $area->getAreaTextField("info") ) ){
			$msg .= "\n". TextFormat::AQUA . $area->getAreaTextField('info');
		}

		$player->sendMessage( $msg );

	}



	/*
	 * Leave area
	 * return @var area, event
	 */
	public function onLeaveArea($area, $ev){

		$player = $ev->getPlayer();
		$player->sendMessage( TextFormat::RED ."Leaving area ". $this->lastArea );
		$this->lastArea = '';

	}

	/*
	 * Player inside area?
	 * return @var bool
	 */
	public function isInside($area, $ev){
		$areapos = $this->areaMinMax($area);
		$plrX = $ev->getTo()->getFloorX();
		$plrY = $ev->getTo()->getFloorY();
		$plrZ = $ev->getTo()->getFloorZ();
		if( $plrX >= $areapos['xmin'] && $plrX <= $areapos['xmax'] &&
		  	$plrY >= $areapos['ymin'] && $plrY <= $areapos['ymax'] &&
		  	$plrZ >= $areapos['zmin'] && $plrZ <= $areapos['zmax'] ){
			return true;
		}else{
			return false;
		}
	}
	/*
	 * Area min/max pos order for difference player pos
	 * return @var array
	 */
	public function areaMinMax($area){
		if( $area->getFirstPosition()->getFloorX() > $area->getSecondPosition()->getFloorX()){
			$aXmin = $area->getSecondPosition()->getFloorX();
			$aXmax = $area->getFirstPosition()->getFloorX();
		}else{
			$aXmin = $area->getFirstPosition()->getFloorX();
			$aXmax = $area->getSecondPosition()->getFloorX();
		}
		if( $area->getFirstPosition()->getFloorY() > $area->getSecondPosition()->getFloorY()){
			$aYmin = $area->getSecondPosition()->getFloorY();
			$aYmax = $area->getFirstPosition()->getFloorY();
		}else{
			$aYmin = $area->getFirstPosition()->getFloorY();
			$aYmax = $area->getSecondPosition()->getFloorY();
		}
		if( $area->getFirstPosition()->getFloorZ() > $area->getSecondPosition()->getFloorZ()){
			$aZmin = $area->getSecondPosition()->getFloorZ();
			$aZmax = $area->getFirstPosition()->getFloorZ();
		}else{
			$aZmin = $area->getFirstPosition()->getFloorZ();
			$aZmax = $area->getSecondPosition()->getFloorZ();
		}
		return array( 'xmin'=>$aXmin, 'xmax'=>$aXmax, 'ymin'=>$aYmin, 'ymax'=>$aYmax, 'zmin'=>$aZmin, 'zmax'=>$aZmax );
	}
	/*
	 * end Genboy edit
	 * sources & examples
	- https://forums.pmmp.io/threads/keep-getting-this-errors.2904/
	- http://forums.pocketmine.net/threads/position-playermoveevent.18220/
	- https://github.com/if-Team/PMMP-Plugins/blob/master/SpeedBlock/src/Khinenw/SpeedBlock/SpeedBlock.php
	- https://github.com/genboy/FloatingTexter/blob/master/src/FloatingTexter/RefreshTask.php
	*/


}
