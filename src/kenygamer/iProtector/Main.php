<?php

/*
 * iProtector-v4.0 plugin for PocketMine-MP
 * Copyright (C) 2014 LDX-MCPE <https://github.com/LDX-MCPE/iProtector>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
*/

namespace kenygamer\iProtector;

use pocketmine\math\Vector3;
use pocketmine\command\{Command, CommandSender, CommandExecutor};
use pocketmine\event\Listener;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityExplodeEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\BlockBreakEvent;

class Main extends PluginBase implements Listener
{
    /** @var array */
    protected $c;
    /** Command prefix */
    const PREFIX = TextFormat::GREEN . "[" . "iProtector" . ":kenygamer" . "]" . TextFormat::RESET . " ";
    
    /**
     * onEnable()
     *
     * Plugin enable
     *
     * @return void
     */
    public function onEnable()
    {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->getLogger()->info(TextFormat::GREEN . "Enabling " . $this->getDescription()->getFullName() . "...");
        $this->getServer()->getLogger()->info(PHP_EOL . "        
   ______            _             _                       _  _    ___  
 (_)  __ \          | |           | |                     | || |  / _ \ 
  _| |__) |_ __ ___ | |_  ___  ___| |_  ___  _ __  __   __| || |_| | | |
 | |  ___/| '__/ _ \| __|/ _ \/ __| __|/ _ \| '__| \ \ / /|__   _| | | |
 | | |    | | | (_) | |_|  __/ (__| |_| (_) | |     \ V /    | |_| |_| |
 |_|_|    |_|  \___/ \__|\___|\___|\__|\___/|_|      \_/     |_(_)\___/ 
                                                                                                                                           


  _             _  __          _                            _                       
 | |           | |/ /         (_)           /\             | |                      
 | |__  _   _  | ' / _____   ___ _ __      /  \   _ __   __| |_ __ _____      _____ 
 | '_ \| | | | |  < / _ \ \ / / | '_ \    / /\ \ | '_ \ / _` | '__/ _ \ \ /\ / / __|
 | |_) | |_| | | . \  __/\ V /| | | | |  / ____ \| | | | (_| | | |  __/\ V  V /\__ \
 |_.__/ \__, | |_|\_\___| \_/ |_|_| |_| /_/    \_\_| |_|\__,_|_|  \___| \_/\_/ |___/
         __/ |                                                                      
        |___/  ");
        
        if (!is_dir($this->getDataFolder())) {
            mkdir($this->getDataFolder());
        }
        if (!file_exists($this->getDataFolder() . "areas.json")) {
            file_put_contents($this->getDataFolder() . "areas.json", "[]");
        }
        if (!file_exists($this->getDataFolder() . "config.yml")) {
            $c = $this->getResource("config.yml");
            $o = stream_get_contents($c);
            fclose($c);
            file_put_contents($this->getDataFolder() . "config.yml", str_replace("DEFAULT", $this->getServer()->getDefaultLevel()->getName(), $o));
        }
        $this->areas = array();
        $data        = json_decode(file_get_contents($this->getDataFolder() . "areas.json"), true);
        foreach ($data as $datum) {
            $area = new Area($datum["name"], $datum["flags"], $datum["pos1"], $datum["pos2"], $datum["level"], $datum["whitelist"], $this);
        }
        $this->c = yaml_parse(file_get_contents($this->getDataFolder() . "config.yml"));
        if ($this->c["Settings"]["Enable"] === false) {
            $this->getPluginLoader()->disablePlugin($this);
            return true;
        } elseif ($this->c["Settings"]["Enable"] !== true) {
            $this->getPluginLoader()->disablePlugin($this);
            return true;
        } else {
            
            $this->god    = $this->c["Default"]["God"];
            $this->edit   = $this->c["Default"]["Edit"];
            $this->tnt    = $this->c["Default"]["TNT"];
            $this->touch  = $this->c["Default"]["Touch"];
            $this->levels = array();
            foreach ($this->c["Worlds"] as $level => $flags) {
                $this->levels[$level] = $flags;
            }
            return true;
        }
    }
    
    /**
     * onDisable()
     *
     * Plugin disable
     *
     * @return void
     */
    public function onDisable()
    {
        $this->getLogger()->info(TextFormat::RED . "Disabling " . $this->getDescription()->getFullName() . "...");
    }
    
    /**
     * onCommand()
     *
     * Plugin commands
     *
     * @param CommandSender $p
     * @param Commmand $cmd
     * @param string $label
     * @param array $args
     *
     * @return bool
     */
    public function onCommand(CommandSender $sender, Command $cmd, string $commandLabel, array $args){
        $p = $sender;
        if (!($p instanceof Player)) {
            $p->sendMessage(self::PREFIX . TextFormat::RED . "Command must be used in-game.");
            return true;
        }
        if (!isset($args[0])) {
            return false;
        }
        $n      = strtolower($p->getName());
        $action = strtolower($args[0]);
        switch ($action) {
            case "pos1":
                if ($p->hasPermission("iprotector") || $p->hasPermission("iprotector.command") || $p->hasPermission("iprotector.command.area") || $p->hasPermission("iprotector.command.area.pos1")) {
                    if (isset($this->sel1[$n]) || isset($this->sel2[$n])) {
                        $o = self::PREFIX . TextFormat::RED . "You're already selecting a position!";
                    } else {
                        $this->sel1[$n] = true;
                        $o              = self::PREFIX . TextFormat::AQUA . "Please place or break the first position.";
                    }
                } else {
                    $o = self::PREFIX . TextFormat::RED . "You do not have permission to use this subcommand.";
                }
                break;
            case "pos2":
                if ($p->hasPermission("iprotector") || $p->hasPermission("iprotector.command") || $p->hasPermission("iprotector.command.area") || $p->hasPermission("iprotector.command.area.pos2")) {
                    if (isset($this->sel1[$n]) || isset($this->sel2[$n])) {
                        $o = self::PREFIX . TextFormat::RED . "You're already selecting a position!";
                    } else {
                        $this->sel2[$n] = true;
                        $o              = self::PREFIX . TextFormat::AQUA . "Please place or break the second position.";
                    }
                } else {
                    $o = self::PREFIX . TextFormat::RED . "You do not have permission to use this subcommand.";
                }
                break;
            case "create":
                if ($p->hasPermission("iprotector") || $p->hasPermission("iprotector.command") || $p->hasPermission("iprotector.command.area") || $p->hasPermission("iprotector.command.area.create")) {
                    if (isset($args[1])) {
                        if (isset($this->pos1[$n]) && isset($this->pos2[$n])) {
                            if (!isset($this->areas[strtolower($args[1])])) {
                                $area = new Area(strtolower($args[1]), array(
                                    "edit" => true,
                                    "god" => false,
                                    "tnt" => false,
                                    "touch" => true
                                ), array(
                                    $this->pos1[$n]->getX(),
                                    $this->pos1[$n]->getY(),
                                    $this->pos1[$n]->getZ()
                                ), array(
                                    $this->pos2[$n]->getX(),
                                    $this->pos2[$n]->getY(),
                                    $this->pos2[$n]->getZ()
                                ), $p->getLevel()->getName(), array(
                                    $n
                                ), $this);
                                $this->saveAreas();
                                unset($this->pos1[$n]);
                                unset($this->pos2[$n]);
                                $o = self::PREFIX . TextFormat::GREEN . "Area created!";
                            } else {
                                $o = self::PREFIX . TextFormat::RED . "An area with that name already exists.";
                            }
                        } else {
                            $o = self::PREFIX . TextFormat::RED . "Please select both positions first.";
                        }
                    } else {
                        $o = self::PREFIX . TextFormat::RED . "Please specify a name for this area.";
                    }
                } else {
                    $o = self::PREFIX . TextFormat::RED . "You do not have permission to use this subcommand.";
                }
                break;
            case "here":
                if ($p->hasPermission("iprotector") || $p->hasPermission("iprotector.command") || $p->hasPermission("iprotector.command.here") || $p->hasPermission("iprotector.command.area.here")) {
                    $contains = false;
                    foreach($this->areas as $area) {
                        if($area->contains(new Vector3($p->getX(), $p->getY(), $p->getZ()), $p->getLevel()->getName())) {
                            $contains = true;
                            $o = self::PREFIX . TextFormat::GREEN . "You are standing on area " . $area->getName() . "." . PHP_EOL . TextFormat::GRAY . "pos1:" . PHP_EOL . TextFormat::GOLD . "X: " . TextFormat::BLUE . $area->getPos1()[0] . PHP_EOL . TextFormat::GOLD . "Y: " . TextFormat::BLUE . $area->getPos1()[1] . PHP_EOL . TextFormat::GOLD . "Z: " . TextFormat::BLUE . $area->getPos1()[2] . PHP_EOL . TextFormat::GRAY . "pos2:" . PHP_EOL . TextFormat::GOLD . "X: " . TextFormat::BLUE . $area->getPos2()[0] . PHP_EOL . TextFormat::GOLD . "Y: " . TextFormat::BLUE . $area->getPos2()[1] . PHP_EOL . TextFormat::GOLD . "Z: " . TextFormat::BLUE . $area->getPos2()[2];
                        }
                    }
                    if($contains === false) {
                        $o = self::PREFIX . TextFormat::RED . "You are not standing in any area.";
                    }
                } else {
                    $o = self::PREFIX . TextFormat::RED . "You do not have permission to use this subcommand.";
                }
                break;
            case "list":
                if ($p->hasPermission("iprotector") || $p->hasPermission("iprotector.command") || $p->hasPermission("iprotector.command.area") || $p->hasPermission("iprotector.command.area.list")) {
                    $o = self::PREFIX . TextFormat::AQUA . "Areas:" . TextFormat::GOLD;
                    foreach ($this->areas as $area) {
                        $o = $o . " " . $area->getName() . ";";
                    }
                }
                break;
            case "flag":
                if ($p->hasPermission("iprotector") || $p->hasPermission("iprotector.command") || $p->hasPermission("iprotector.command.area") || $p->hasPermission("iprotector.command.area.flag")) {
                    if (isset($args[1])) {
                        if (isset($this->areas[strtolower($args[1])])) {
                            $area = $this->areas[strtolower($args[1])];
                            if (isset($args[2])) {
                                if (isset($area->flags[strtolower($args[2])])) {
                                    $flag = strtolower($args[2]);
                                    if (isset($args[3])) {
                                        $mode = strtolower($args[3]);
                                        if ($mode == "true" || $mode == "on") {
                                            $mode = true;
                                        } else {
                                            $mode = false;
                                        }
                                        $area->setFlag($flag, $mode);
                                    } else {
                                        $area->toggleFlag($flag);
                                    }
                                    if ($area->getFlag($flag)) {
                                        $status = "on";
                                    } else {
                                        $status = "off";
                                    }
                                    $o = self::PREFIX . TextFormat::GREEN . "Flag " . $flag . " set to " . $status . " for area " . $area->getName() . "!";
                                } else {
                                    $o = self::PREFIX . TextFormat::RED . "Flag not found. (Flags: edit, god, tnt, touch)";
                                }
                            } else {
                                $o = self::PREFIX . TextFormat::RED . "Please specify a flag. (Flags: edit, god, tnt, touch)";
                            }
                        } else {
                            $o = self::PREFIX . TextFormat::RED . "Area doesn't exist.";
                        }
                    } else {
                        $o = self::PREFIX . TextFormat::RED . "Please specify the area you would like to flag.";
                    }
                } else {
                    $o = self::PREFIX . TextFormat::RED . "You do not have permission to use this subcommand.";
                }
                break;
            case "delete":
                if ($p->hasPermission("iprotector") || $p->hasPermission("iprotector.command") || $p->hasPermission("iprotector.command.area") || $p->hasPermission("iprotector.command.area.delete")) {
                    if (isset($args[1])) {
                        if (isset($this->areas[strtolower($args[1])])) {
                            $area = $this->areas[strtolower($args[1])];
                            $area->delete();
                            $o = self::PREFIX . TextFormat::GREEN . "Area deleted!";
                        } else {
                            $o = self::PREFIX . TextFormat::RED . "Area does not exist.";
                        }
                    } else {
                        $o = self::PREFIX . TextFormat::RED . "Please specify an area to delete.";
                    }
                } else {
                    $o = self::PREFIX . TextFormat::RED . "You do not have permission to use this subcommand.";
                }
                break;
            case "whitelist":
                if ($p->hasPermission("iprotector") || $p->hasPermission("iprotector.command") || $p->hasPermission("iprotector.command.area") || $p->hasPermission("iprotector.command.area.delete")) {
                    if (isset($args[1]) && isset($this->areas[strtolower($args[1])])) {
                        $area = $this->areas[strtolower($args[1])];
                        if (isset($args[2])) {
                            $action = strtolower($args[2]);
                            switch ($action) {
                                case "add":
                                    $w = ($this->getServer()->getPlayer($args[3]) instanceof Player ? strtolower($this->getServer()->getPlayer($args[3])->getName()) : strtolower($args[3]));
                                    if (!$area->isWhitelisted($w)) {
                                        $area->setWhitelisted($w);
                                        $o = self::PREFIX . TextFormat::GREEN . "Player $w has been whitelisted in area " . $area->getName() . ".";
                                    } else {
                                        $o = self::PREFIX . TextFormat::RED . "Player $w is already whitelisted in area " . $area->getName() . ".";
                                    }
                                    break;
                                case "list":
                                    $o = self::PREFIX . TextFormat::AQUA . $area->getName() . "'s whitelist:" . PHP_EOL;
                                    foreach ($area->getWhitelist() as $w) {
                                        $o .= " $w;";
                                    }
                                    break;
                                case "delete":
                                case "remove":
                                    $w = ($this->getServer()->getPlayer($args[3]) instanceof Player ? strtolower($this->getServer()->getPlayer($args[3])->getName()) : strtolower($args[3]));
                                    if ($area->isWhitelisted($w)) {
                                        $area->setWhitelisted($w, false);
                                        $o = self::PREFIX . TextFormat::GREEN . "Player $w has been unwhitelisted in area " . $area->getName() . ".";
                                    } else {
                                        $o = self::PREFIX . TextFormat::RED . "$w is already unwhitelisted in area " . $area->getName() . ".";
                                    }
                                    break;
                                default:
                                    $o = self::PREFIX . TextFormat::RED . "Please specify a valid action. Usage: /area whitelist " . $area->getName() . " <add/list/remove> [player]";
                                    break;
                            }
                        } else {
                            $o = self::PREFIX . TextFormat::RED . "Please specify an action. Usage: /area whitelist " . $area->getName() . " <add/list/remove> [player]";
                        }
                    } else {
                        $o = self::PREFIX . TextFormat::RED . "Area doesn't exist. Usage: /area whitelist <area> <add/list/remove> [player]";
                    }
                } else {
                    $o = self::PREFIX . TextFormat::RED . "You do not have permission to use this subcommand.";
                }
                break;
            default:
                return false;
                break;
        }
        $p->sendMessage($o);
        return true;
    }
    
    /**
     * onHurt()
     *
     * EntityDamageEvent
     *
     * @param EntityDamageEvent $event
     *
     * @return void
     */
    public function onHurt(EntityDamageEvent $event)
    {
        if ($event->getEntity() instanceof Player) {
            $p = $event->getEntity();
            $x = false;
            if (!$this->canGetHurt($p)) {
                //$c = yaml_parse(file_get_contents($this->getDataFolder() . "config.yml"));
                if ($this->c["Messages"]["Hurt"]["Enable"] === true) {
                    $p->sendMessage(str_replace('{player}', $p->getName(), $this->c["Messages"]["Hurt"]["Message"]));
                }
                $event->setCancelled();
            }
        }
    }
    
    /**
     * onBlockBreak()
     *
     * BlockBreakEvent
     *
     * @param BlockBreakEvent $event
     *
     * @return void
     */
    public function onBlockBreak(BlockBreakEvent $event)
    {
        $b = $event->getBlock();
        $p = $event->getPlayer();
        $n = strtolower($p->getName());
        if (isset($this->sel1[$n])) {
            unset($this->sel1[$n]);
            $this->pos1[$n] = new Vector3($b->getX(), $b->getY(), $b->getZ());
            $p->sendMessage(self::PREFIX . TextFormat::GREEN . "Position 1 set to: (" . $this->pos1[$n]->getX() . ", " . $this->pos1[$n]->getY() . ", " . $this->pos1[$n]->getZ() . ")");
            $event->setCancelled();
        } else if (isset($this->sel2[$n])) {
            unset($this->sel2[$n]);
            $this->pos2[$n] = new Vector3($b->getX(), $b->getY(), $b->getZ());
            $p->sendMessage(self::PREFIX . TextFormat::GREEN . "Position 2 set to: (" . $this->pos2[$n]->getX() . ", " . $this->pos2[$n]->getY() . ", " . $this->pos2[$n]->getZ() . ")");
            $event->setCancelled();
        } else {
            if (!$this->canEdit($p, $b)) {
                //$c = yaml_parse(file_get_contents($this->getDataFolder() . "config.yml"));
                if ($this->c["Messages"]["Break"]["Enable"] === true) {
                    $p->sendMessage(str_replace('{block}', $b->getName(), $this->c["Messages"]["Break"]["Message"]));
                }
                $event->setCancelled();
            }
        }
    }
    
    /**
     * onBlockPlace()
     *
     * BlockPlaceEvent
     *
     * @param BlockPlaceEvent $event
     *
     * @return void
     */
    public function onBlockPlace(BlockPlaceEvent $event)
    {
        $b = $event->getBlock();
        $p = $event->getPlayer();
        $n = strtolower($p->getName());
        if (isset($this->sel1[$n])) {
            unset($this->sel1[$n]);
            $this->pos1[$n] = new Vector3($b->getX(), $b->getY(), $b->getZ());
            $p->sendMessage(self::PREFIX . TextFormat::GREEN . "Position 1 set to: (" . $this->pos1[$n]->getX() . ", " . $this->pos1[$n]->getY() . ", " . $this->pos1[$n]->getZ() . ")");
            $event->setCancelled();
        } else if (isset($this->sel2[$n])) {
            unset($this->sel2[$n]);
            $this->pos2[$n] = new Vector3($b->getX(), $b->getY(), $b->getZ());
            $p->sendMessage(self::PREFIX . TextFormat::GREEN . "Position 2 set to: (" . $this->pos2[$n]->getX() . ", " . $this->pos2[$n]->getY() . ", " . $this->pos2[$n]->getZ() . ")");
            $event->setCancelled();
        } else {
            if (!$this->canEdit($p, $b)) {
                $c = yaml_parse(file_get_contents($this->getDataFolder() . "config.yml"));
                if ($this->c["Messages"]["Place"]["Enable"] === true) {
                    $p->sendMessage(str_replace('{block}', $b->getName(), $c["Messages"]["Place"]["Message"]));
                }
                $event->setCancelled();
            }
        }
    }
    
    /**
     * onBlockTouch()
     *
     * PlayerInteractEvent
     *
     * @param PlayerInteractEvent $event
     *
     * @return void
     */
    public function onBlockTouch(PlayerInteractEvent $event)
    {
        $b = $event->getBlock();
        $p = $event->getPlayer();
        if (!$this->canTouch($p, $b)) {
            //$c = yaml_parse(file_get_contents($this->getDataFolder() . "config.yml"));
            if ($this->c["Messages"]["Touch"]["Enable"] === true) {
                $p->sendMessage(str_replace('{block}', $b->getName(), $this->c["Messages"]["Touch"]["Message"]));
            }
            $event->setCancelled();
        }
    }
    
    /* Handles TNT flag */
    
    /**
     * OnEntityExplode()
     *
     * EntityExplodeEvent
     *
     * @param EntityExplodeEvent $event
     *
     * @return void
     */
    public function onEntityExplode(EntityExplodeEvent $event)
    {
        if (!$this->canExplode($event->getPosition(), $event->getEntity()->getLevel())) {
            $event->setCancelled();
        }
    }
    
    /**
     * saveAreas()
     *
     * @api
     *
     * Saves plugin areas and any changes made previously
     *
     * @return void
     */
    public function saveAreas()
    {
        $areas = array();
        foreach ($this->areas as $area) {
            $areas[] = array(
                "name" => $area->getName(),
                "flags" => $area->getFlags(),
                "pos1" => $area->getPos1(),
                "pos2" => $area->getPos2(),
                "level" => $area->getLevel(),
                "whitelist" => $area->getWhitelist()
            );
        }
        //$c = yaml_parse(file_get_contents($this->getDataFolder() . "config.yml"));
        if ($this->c["Settings"]["JPP"] === true) {
            file_put_contents($this->getDataFolder() . "areas.json", json_encode($areas, JSON_PRETTY_PRINT));
        } elseif($this->c["Settings"]["JPP"] === false) {
            file_put_contents($this->getDataFolder() . "areas.json", json_encode($areas));
        } else {
            file_put_contents($this->getDataFolder() . "areas.json", json_encode($areas));
        }
    }
    
    /**
     * canEdit()
     *
     * @api
     *
     * Checks if player can edit the given position
     *
     * @param Player $p
     * @param Block $t
     *
     * @return bool
     */
    public function canEdit($p, $t)
    {
        if ($p->hasPermission("iprotector") || $p->hasPermission("iprotector.access")) {
            return true;
        }
        $o = true;
        $g = (isset($this->levels[$t->getLevel()->getName()]) ? $this->levels[$t->getLevel()->getName()]["Edit"] : $this->edit);
        if ($g) {
            $o = false;
        }
        foreach ($this->areas as $area) {
            if ($area->contains(new Vector3($t->getX(), $t->getY(), $t->getZ()), $t->getLevel()->getName())) {
                if ($area->getFlag("edit")) {
                    $o = false;
                }
                if ($area->isWhitelisted(strtolower($p->getName()))) {
                    $o = true;
                    break;
                }
                if (!$area->getFlag("edit") && $g) {
                    $o = true;
                    break;
                }
            }
        }
        return $o;
    }
    
    /**
     * canTouch()
     *
     * @api
     *
     * Checks if player can touch the given position
     *
     * @param Player $p
     * @param Block $t
     *
     * @return bool
     */
    public function canTouch($p, $t)
    {
        if ($p->hasPermission("iprotector") || $p->hasPermission("iprotector.access")) {
            return true;
        }
        $o = true;
        $g = (isset($this->levels[$t->getLevel()->getName()]) ? $this->levels[$t->getLevel()->getName()]["Touch"] : $this->touch);
        if ($g) {
            $o = false;
        }
        foreach ($this->areas as $area) {
            if ($area->contains(new Vector3($t->getX(), $t->getY(), $t->getZ()), $t->getLevel()->getName())) {
                if ($area->getFlag("touch")) {
                    $o = false;
                }
                if ($area->isWhitelisted(strtolower($p->getName()))) {
                    $o = true;
                    break;
                }
                if (!$area->getFlag("touch") && $g) {
                    $o = true;
                    break;
                }
            }
        }
        return $o;
    }
    
    /**
     * canGetHurt()
     *
     * @api
     *
     * Checks if player can get hurt on given position
     *
     * @param Player $p
     *
     * @return bool
     */
    public function canGetHurt($p)
    {
        $o = true;
        $g = (isset($this->levels[$p->getLevel()->getName()]) ? $this->levels[$p->getLevel()->getName()]["God"] : $this->god);
        if ($g) {
            $o = false;
        }
        foreach ($this->areas as $area) {
            if ($area->contains(new Vector3($p->getX(), $p->getY(), $p->getZ()), $p->getLevel()->getName())) {
                if (!$area->getFlag("god") && $g) {
                    $o = true;
                    break;
                }
                if ($area->getFlag("god")) {
                    $o = false;
                }
            }
        }
        return $o;
    }
    
    /**
     * canExplode()
     *
     * @api
     *
     * Checks if entity can explode on given position
     *
     * @param pocketmine\level\Position $pos
     * @param pocketmine\level\Level $level
     *
     * @return bool
     */
    public function canExplode($pos, $level)
    {
        $o = true;
        $g = (isset($this->levels[$level->getName()]) ? $this->levels[$level->getName()]["TNT"] : $this->tnt);
        if ($g) {
            $o = false;
        }
        foreach ($this->areas as $area) {
            if ($area->contains(new Vector3($pos->getX(), $pos->getY(), $pos->getZ()), $level->getName())) {
                if ($area->getFlag("tnt")) {
                    $o = false;
                    break;
                }
                if ($area->getFlag("tnt") && $g) {
                    $o = true;
                    break;
                }
            }
        }
        return $o;
    }
    
}
