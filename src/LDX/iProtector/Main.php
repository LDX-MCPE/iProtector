<?php

namespace LDX\iProtector;

use pocketmine\math\Vector3;
use pocketmine\command\Command;
use pocketmine\command\CommandExecutor;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\BlockBreakEvent;

class Main extends PluginBase implements Listener {

  public function onEnable() {
    $this->getServer()->getPluginManager()->registerEvents($this,$this);
    if(!is_dir($this->getDataFolder())) {
      mkdir($this->getDataFolder());
    }
    if(!file_exists($this->getDataFolder() . "areas.json")) {
      file_put_contents($this->getDataFolder() . "areas.json","[]");
    }
    if(!file_exists($this->getDataFolder() . "config.yml")) {
      $c = $this->getResource("config.yml");
      $o = stream_get_contents($c);
      fclose($c);
      file_put_contents($this->getDataFolder() . "config.yml",str_replace("DEFAULT",$this->getServer()->getDefaultLevel()->getName(),$o));
    }
    $this->areas = array();
    $data = json_decode(file_get_contents($this->getDataFolder() . "areas.json"),true);
    foreach($data as $datum) {
      $area = new Area($datum["name"],$datum["flags"],$datum["pos1"],$datum["pos2"],$datum["level"],$datum["whitelist"],$this);
    }
    $c = yaml_parse(file_get_contents($this->getDataFolder() . "config.yml"));
    $this->god = $c["Default"]["God"];
    $this->edit = $c["Default"]["Edit"];
    $this->touch = $c["Default"]["Touch"];
    $this->levels = array();
    foreach($c["Worlds"] as $level => $flags) {
      $this->levels[$level] = $flags;
    }
  }

  public function onCommand(CommandSender $p,Command $cmd,$label,array $args) {
    if(!($p instanceof Player)) {
      $p->sendMessage(TextFormat::RED . "Command must be used in-game.");
      return true;
    }
    if(!isset($args[0])) {
      return false;
    }
    $n = strtolower($p->getName());
    $action = strtolower($args[0]);
    switch($action) {
      case "pos1":
        if($p->hasPermission("iprotector") || $p->hasPermission("iprotector.command") || $p->hasPermission("iprotector.command.area") || $p->hasPermission("iprotector.command.area.pos1")) {
          if(isset($this->sel1[$n]) || isset($this->sel2[$n])) {
            $o = "You're already selecting a position!";
          } else {
            $this->sel1[$n] = true;
            $o = "Please place or break the first position.";
          }
        } else {
          $o = "You do not have permission to use this subcommand.";
        }
      break;
      case "pos2":
        if($p->hasPermission("iprotector") || $p->hasPermission("iprotector.command") || $p->hasPermission("iprotector.command.area") || $p->hasPermission("iprotector.command.area.pos2")) {
          if(isset($this->sel1[$n]) || isset($this->sel2[$n])) {
            $o = "You're already selecting a position!";
          } else {
            $this->sel2[$n] = true;
            $o = "Please place or break the second position.";
          }
        } else {
          $o = "You do not have permission to use this subcommand.";
        }
      break;
      case "create":
        if($p->hasPermission("iprotector") || $p->hasPermission("iprotector.command") || $p->hasPermission("iprotector.command.area") || $p->hasPermission("iprotector.command.area.create")) {
          if(isset($args[1])) {
            if(isset($this->pos1[$n]) && isset($this->pos2[$n])) {
              if(!isset($this->areas[strtolower($args[1])])) {
                $area = new Area(strtolower($args[1]),array("edit" => true,"god" => false,"touch" => true),array($this->pos1[$n]->getX(),$this->pos1[$n]->getY(),$this->pos1[$n]->getZ()),array($this->pos2[$n]->getX(),$this->pos2[$n]->getY(),$this->pos2[$n]->getZ()),$p->getLevel()->getName(),array($n),$this);
                $this->saveAreas();
                unset($this->pos1[$n]);
                unset($this->pos2[$n]);
                $o = "Area created!";
              } else {
                $o = "An area with that name already exists.";
              }
            } else {
              $o = "Please select both positions first.";
            }
          } else {
            $o = "Please specify a name for this area.";
          }
        } else {
          $o = "You do not have permission to use this subcommand.";
        }
      break;
      case "list":
        if($p->hasPermission("iprotector") || $p->hasPermission("iprotector.command") || $p->hasPermission("iprotector.command.area") || $p->hasPermission("iprotector.command.area.list")) {
          $o = "Areas:";
          foreach($this->areas as $area) {
            $o = $o . " " . $area->getName() . ";";
          }
        }
      break;
      case "flag":
        if($p->hasPermission("iprotector") || $p->hasPermission("iprotector.command") || $p->hasPermission("iprotector.command.area") || $p->hasPermission("iprotector.command.area.flag")) {
          if(isset($args[1])) {
            if(isset($this->areas[strtolower($args[1])])) {
              $area = $this->areas[strtolower($args[1])];
              if(isset($args[2])) {
                if(isset($area->flags[strtolower($args[2])])) {
                  $flag = strtolower($args[2]);
                  if(isset($args[3])) {
                    $mode = strtolower($args[3]);
                    if($mode == "true" || $mode == "on") {
                      $mode = true;
                    } else {
                      $mode = false;
                    }
                    $area->setFlag($flag,$mode);
                  } else {
                    $area->toggleFlag($flag);
                  }
                  if($area->getFlag($flag)) {
                    $status = "on";
                  } else {
                    $status = "off";
                  }
                  $o = "Flag " . $flag . " set to " . $status . " for area " . $area->getName() . "!";
                } else {
                  $o = "Flag not found. (Flags: edit, god, touch)";
                }
              } else {
                $o = "Please specify a flag. (Flags: edit, god, touch)";
              }
            } else {
              $o = "Area doesn't exist.";
            }
          } else {
            $o = "Please specify the area you would like to flag.";
          }
        } else {
          $o = "You do not have permission to use this subcommand.";
        }
      break;
      case "delete":
        if($p->hasPermission("iprotector") || $p->hasPermission("iprotector.command") || $p->hasPermission("iprotector.command.area") || $p->hasPermission("iprotector.command.area.delete")) {
          if(isset($args[1])) {
            if(isset($this->areas[strtolower($args[1])])) {
              $area = $this->areas[strtolower($args[1])];
              $area->delete();
              $o = "Area deleted!";
            } else {
              $o = "Area does not exist.";
            }
          } else {
            $o = "Please specify an area to delete.";
          }
        } else {
          $o = "You do not have permission to use this subcommand.";
        }
      break;
      case "whitelist":
        if($p->hasPermission("iprotector") || $p->hasPermission("iprotector.command") || $p->hasPermission("iprotector.command.area") || $p->hasPermission("iprotector.command.area.delete")) {
          if(isset($args[1]) && isset($this->areas[strtolower($args[1])])) {
            $area = $this->areas[strtolower($args[1])];
            if(isset($args[2])) {
              $action = strtolower($args[2]);
              switch($action) {
                case "add":
                  $w = ($this->getServer()->getPlayer($args[3]) instanceof Player ? strtolower($this->getServer()->getPlayer($args[3])->getName()) : strtolower($args[3]));
                  if(!$area->isWhitelisted($w)) {
                    $area->setWhitelisted($w);
                    $o = "Player $w has been whitelisted in area " . $area->getName() . ".";
                  } else {
                    $o = "Player $w is already whitelisted in area " . $area->getName() . ".";
                  }
                break;
                case "list":
                  $o = "Area " . $area->getName() . "'s whitelist:";
                  foreach($area->getWhitelist() as $w) {
                    $o .= " $w;";
                  }
                break;
                case "delete":
                case "remove":
                  $w = ($this->getServer()->getPlayer($args[3]) instanceof Player ? strtolower($this->getServer()->getPlayer($args[3])->getName()) : strtolower($args[3]));
                  if($area->isWhitelisted($w)) {
                    $area->setWhitelisted($w,false);
                    $o = "Player $w has been unwhitelisted in area " . $area->getName() . ".";
                  } else {
                    $o = "Player $w is already unwhitelisted in area " . $area->getName() . ".";
                  }
                break;
                default:
                  $o = "Please specify a valid action. Usage: /area whitelist " . $area->getName() . " <add/list/remove> [player]";
                break;
              }
            } else {
              $o = "Please specify an action. Usage: /area whitelist " . $area->getName() . " <add/list/remove> [player]";
            }
          } else {
            $o = "Area doesn't exist. Usage: /area whitelist <area> <add/list/remove> [player]";
          }
        } else {
          $o = "You do not have permission to use this subcommand.";
        }
      break;
      default:
        return false;
      break;
    }
    $p->sendMessage($o);
    return true;
  }

  public function onHurt(EntityDamageEvent $event) {
    if($event->getEntity() instanceof Player) {
      $p = $event->getEntity();
      $x = false;
      if(!$this->canGetHurt($p)) {
       $event->setCancelled();
}
        if($event instanceof EntityDamageByEntityEvent){
            if($event->getDamager() instanceof Player){
      $p = $event->getDamager();
      if(!$this->canGetHurt($p)) {
        $mes = "§6§lHey! §r§4You can't hit players here!";
        $p->sendMessage($mes);
        $event->setCancelled();
        }
      }
    }
  }
}

  public function onBlockBreak(BlockBreakEvent $event) {
    $b = $event->getBlock();
    $p = $event->getPlayer();
    $n = strtolower($p->getName());
    if(isset($this->sel1[$n])) {
      unset($this->sel1[$n]);
      $this->pos1[$n] = new Vector3($b->getX(),$b->getY(),$b->getZ());
      $p->sendMessage("Position 1 set to: (" . $this->pos1[$n]->getX() . ", " . $this->pos1[$n]->getY() . ", " . $this->pos1[$n]->getZ() . ")");
      $event->setCancelled();
    } else if(isset($this->sel2[$n])) {
      unset($this->sel2[$n]);
      $this->pos2[$n] = new Vector3($b->getX(),$b->getY(),$b->getZ());
      $p->sendMessage("Position 2 set to: (" . $this->pos2[$n]->getX() . ", " . $this->pos2[$n]->getY() . ", " . $this->pos2[$n]->getZ() . ")");
      $event->setCancelled();
    } else {
      if(!$this->canEdit($p,$b)) {
        $event->setCancelled();
      }
    }
  }

  public function onBlockPlace(BlockPlaceEvent $event) {
    $b = $event->getBlock();
    $p = $event->getPlayer();
    $n = strtolower($p->getName());
    if(isset($this->sel1[$n])) {
      unset($this->sel1[$n]);
      $this->pos1[$n] = new Vector3($b->getX(),$b->getY(),$b->getZ());
      $p->sendMessage("Position 1 set to: (" . $this->pos1[$n]->getX() . ", " . $this->pos1[$n]->getY() . ", " . $this->pos1[$n]->getZ() . ")");
      $event->setCancelled();
    } else if(isset($this->sel2[$n])) {
      unset($this->sel2[$n]);
      $this->pos2[$n] = new Vector3($b->getX(),$b->getY(),$b->getZ());
      $p->sendMessage("Position 2 set to: (" . $this->pos2[$n]->getX() . ", " . $this->pos2[$n]->getY() . ", " . $this->pos2[$n]->getZ() . ")");
      $event->setCancelled();
    } else {
      if(!$this->canEdit($p,$b)) {
        $event->setCancelled();
      }
    }
  }

  public function onBlockTouch(PlayerInteractEvent $event) {
    $b = $event->getBlock();
    $p = $event->getPlayer();
    if(!$this->canTouch($p,$b)) {
      $event->setCancelled();
    }
  }

  public function saveAreas() {
    $areas = array();
    foreach($this->areas as $area) {
      $areas[] = array("name" => $area->getName(),"flags" => $area->getFlags(),"pos1" => $area->getPos1(),"pos2" => $area->getPos2(),"level" => $area->getLevel(),"whitelist" => $area->getWhitelist());
    }
    file_put_contents($this->getDataFolder() . "areas.json",json_encode($areas));
  }

  public function canEdit($p,$t) {
    if($p->hasPermission("iprotector") || $p->hasPermission("iprotector.access")) {
      return true;
    }
    $o = true;
    $g = (isset($this->levels[$t->getLevel()->getName()]) ? $this->levels[$t->getLevel()->getName()]["Edit"] : $this->edit);
    if($g) {
      $o = false;
    }
    foreach($this->areas as $area) {
      if($area->contains(new Vector3($t->getX(),$t->getY(),$t->getZ()),$t->getLevel()->getName())) {
        if($area->getFlag("edit")) {
          $o = false;
        }
        if($area->isWhitelisted(strtolower($p->getName()))) {
          $o = true;
          break;
        }
        if(!$area->getFlag("edit") && $g) {
          $o = true;
          break;
        }
      }
    }
    return $o;
  }

  public function canTouch($p,$t) {
    if($p->hasPermission("iprotector") || $p->hasPermission("iprotector.access")) {
      return true;
    }
    $o = true;
    $g = (isset($this->levels[$t->getLevel()->getName()]) ? $this->levels[$t->getLevel()->getName()]["Touch"] : $this->touch);
    if($g) {
      $o = false;
    }
    foreach($this->areas as $area) {
      if($area->contains(new Vector3($t->getX(),$t->getY(),$t->getZ()),$t->getLevel()->getName())) {
        if($area->getFlag("touch")) {
          $o = false;
        }
        if($area->isWhitelisted(strtolower($p->getName()))) {
          $o = true;
          break;
        }
        if(!$area->getFlag("touch") && $g) {
          $o = true;
          break;
        }
      }
    }
    return $o;
  }

  public function canGetHurt($p) {
    $o = true;
    $g = (isset($this->levels[$p->getLevel()->getName()]) ? $this->levels[$p->getLevel()->getName()]["God"] : $this->god);
    if($g) {
      $o = false;
    }
    foreach($this->areas as $area) {
      if($area->contains(new Vector3($p->getX(),$p->getY(),$p->getZ()),$p->getLevel()->getName())) {
        if(!$area->getFlag("god") && $g) {
          $o = true;
          break;
        }
        if($area->getFlag("god")) {
          $o = false;
        }
      }
    }
    return $o;
  }

}
